<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ComprobanteElectronicoFe;
use App\Models\HaciendaComprobante;
use App\Services\Hacienda\HaciendaApiClient;
use App\Services\Hacienda\Xml\FirmaDigitalService;
use App\Services\Hacienda\Xml\XmlComprobanteBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * API Controller - Integración con Hacienda Costa Rica
 *
 * Endpoints para envío, validación y seguimiento de comprobantes electrónicos
 * hacia el sistema del Ministerio de Hacienda.
 *
 * @package App\Http\Controllers\Api\V1
 */
class HaciendaController extends ApiController
{
    public function __construct(
        protected XmlComprobanteBuilder $xmlBuilder,
        protected FirmaDigitalService $firmaService,
        protected HaciendaApiClient $apiClient,
    ) {}

    /**
     * POST /api/v1/hacienda/generar
     * Generar comprobante electrónico para envío a Hacienda
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generar(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'comprobante_id' => 'required|integer|exists:comprobantes_electronicos_fe,id',
                'tipo' => 'sometimes|string|in:01,03,04,05,07',
            ]);

            $comprobante = ComprobanteElectronicoFe::findOrFail($request->input('comprobante_id'));
            $tipo = $request->input('tipo', '01');

            // Verificar si ya existe en Hacienda
            $existing = HaciendaComprobante::where('comprobante_id', $comprobante->id)->first();
            if ($existing) {
                return $this->success([
                    'id' => $existing->id,
                    'clave' => $existing->clave,
                    'estado' => $existing->estado,
                    'tipo' => $existing->tipo_comprobante,
                ], 'Comprobante ya existe', 200);
            }

            $haciendaComprobante = HaciendaComprobante::create([
                'comprobante_id' => $comprobante->id,
                'empresa_id' => $comprobante->empresa_id,
                'clave' => $comprobante->clave,
                'tipo_comprobante' => $tipo,
                'estado' => 'pending',
            ]);

            return $this->success([
                'id' => $haciendaComprobante->id,
                'clave' => $haciendaComprobante->clave,
                'estado' => $haciendaComprobante->estado,
                'tipo' => $haciendaComprobante->tipo_comprobante,
            ], 'Comprobante generado exitosamente', 201);
        } catch (\Exception $e) {
            return $this->error(config('app.debug') ? $e->getMessage() : 'Error interno del servidor', 500);
        }
    }

    /**
     * POST /api/v1/hacienda/{id}/generar-xml
     * Generar XML del comprobante
     *
     * @param int $id
     * @return JsonResponse
     */
    public function generarXml(int $id): JsonResponse
    {
        try {
            $haciendaComprobante = HaciendaComprobante::findOrFail($id);
            $comprobante = ComprobanteElectronicoFe::findOrFail($haciendaComprobante->comprobante_id);

            $xml = $this->xmlBuilder->build($comprobante);

            $haciendaComprobante->update(['xml_contenido' => $xml]);

            return $this->success([
                'id' => $haciendaComprobante->id,
                'xml_preview' => substr($xml, 0, 500) . '...',
            ], 'XML generado exitosamente');
        } catch (\Exception $e) {
            return $this->error(config('app.debug') ? $e->getMessage() : 'Error interno del servidor', 500);
        }
    }

    /**
     * POST /api/v1/hacienda/{id}/firmar
     * Firmar comprobante con certificado digital XAdES-EPES
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function firmar(int $id, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'certificado_id' => 'required|integer|exists:fe_certificados_digitales,id',
            ]);

            $haciendaComprobante = HaciendaComprobante::findOrFail($id);

            if (!$haciendaComprobante->xml_contenido) {
                return $this->error('El comprobante no tiene XML generado. Llame primero a generar-xml.', 422);
            }

            $xmlFirmado = $this->firmaService->firmar(
                $haciendaComprobante->xml_contenido,
                $request->input('certificado_id')
            );

            $haciendaComprobante->markAsSigned();
            $haciendaComprobante->update(['xml_contenido' => $xmlFirmado]);

            return $this->success([
                'id' => $haciendaComprobante->id,
                'estado' => $haciendaComprobante->fresh()->estado,
            ], 'Comprobante firmado exitosamente');
        } catch (\Exception $e) {
            return $this->error(config('app.debug') ? $e->getMessage() : 'Error interno del servidor', 500);
        }
    }

    /**
     * POST /api/v1/hacienda/{id}/enviar
     * Enviar comprobante a la API de Hacienda
     *
     * @param int $id
     * @return JsonResponse
     */
    public function enviar(int $id): JsonResponse
    {
        try {
            $haciendaComprobante = HaciendaComprobante::findOrFail($id);

            if (!$haciendaComprobante->isReadyForSending()) {
                return $this->error('El comprobante no está listo para envío. Debe estar firmado.', 422);
            }

            $comprobante = ComprobanteElectronicoFe::findOrFail($haciendaComprobante->comprobante_id);

            $resultado = $this->apiClient->enviarComprobante(
                $haciendaComprobante->clave,
                base64_encode($haciendaComprobante->xml_contenido),
                $comprobante->fecha_emision->toIso8601String(),
                [
                    'tipoIdentificacion' => $comprobante->empresa->tipo_identificacion ?? '01',
                    'numeroIdentificacion' => $comprobante->empresa->numero_identificacion ?? '',
                ],
                $comprobante->receptor_numero_identificacion ? [
                    'tipoIdentificacion' => $comprobante->receptor_tipo_identificacion,
                    'numeroIdentificacion' => $comprobante->receptor_numero_identificacion,
                ] : null
            );

            if ($resultado['success'] ?? false) {
                $haciendaComprobante->markAsSent();
            } else {
                $haciendaComprobante->markAsError($resultado['error'] ?? 'Error desconocido');
            }

            return $this->success([
                'id' => $haciendaComprobante->id,
                'clave' => $haciendaComprobante->clave,
                'estado' => $haciendaComprobante->fresh()->estado,
                'respuesta' => $resultado,
            ], $resultado['success'] ? 'Comprobante enviado a Hacienda exitosamente' : 'Error al enviar');
        } catch (\Exception $e) {
            return $this->error(config('app.debug') ? $e->getMessage() : 'Error interno del servidor', 500);
        }
    }

    /**
     * GET /api/v1/hacienda/{id}/estado
     * Consultar estado de comprobante en Hacienda
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getEstado(int $id): JsonResponse
    {
        try {
            $haciendaComprobante = HaciendaComprobante::findOrFail($id);

            $status = $this->apiClient->consultarEstado($haciendaComprobante->clave);

            if (!$status['success']) {
                return $this->error('No se pudo obtener el estado', 422);
            }

            // Sincronizar estado local si cambió
            $haciendaEstado = $status['data']['ind-estado'] ?? null;
            if ($haciendaEstado) {
                $nuevoEstado = match ($haciendaEstado) {
                    'aceptado' => 'accepted',
                    'rechazado' => 'rejected',
                    'recibido', 'procesando' => 'sent',
                    default => $haciendaComprobante->estado,
                };
                if ($nuevoEstado !== $haciendaComprobante->estado) {
                    $haciendaComprobante->updateEstado($nuevoEstado);
                }
            }

            return $this->success($status['data'], 'Estado obtenido exitosamente');
        } catch (\Exception $e) {
            return $this->error(config('app.debug') ? $e->getMessage() : 'Error interno del servidor', 500);
        }
    }

    /**
     * GET /api/v1/hacienda/estadisticas
     * Obtener estadísticas de comprobantes
     *
     * @return JsonResponse
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $stats = [
                'total' => HaciendaComprobante::count(),
                'pending' => HaciendaComprobante::pending()->count(),
                'signed' => HaciendaComprobante::signed()->count(),
                'sent' => HaciendaComprobante::sent()->count(),
                'accepted' => HaciendaComprobante::accepted()->count(),
                'rejected' => HaciendaComprobante::rejected()->count(),
            ];

            return $this->success($stats, 'Estadísticas de comprobantes');
        } catch (\Exception $e) {
            return $this->error(config('app.debug') ? $e->getMessage() : 'Error interno del servidor', 500);
        }
    }

    /**
     * GET /api/v1/hacienda
     * Listar comprobantes enviados a Hacienda
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Lista comprobantes con filtros comunes (estado, tipo, empresa,
            // clave) y paginación. Devuelve recursos paginados para uso
            // eficiente en UI.
            $query = HaciendaComprobante::with('comprobante', 'empresa');

            // Filtros
            if ($request->has('estado')) {
                $query->where('estado', $request->input('estado'));
            }

            if ($request->has('tipo')) {
                $query->where('tipo_comprobante', $request->input('tipo'));
            }

            if ($request->has('empresa_id')) {
                $query->where('empresa_id', $request->input('empresa_id'));
            }

            if ($request->has('clave')) {
                $query->where('clave', $request->input('clave'));
            }

            $haciendaComprobantes = $query->paginate($request->input('per_page', 15));

            return $this->success($haciendaComprobantes);
        } catch (\Exception $e) {
            return $this->error(config('app.debug') ? $e->getMessage() : 'Error interno del servidor', 500);
        }
    }

    /**
     * GET /api/v1/hacienda/{id}
     * Obtener detalles de un comprobante
     *
     * @param HaciendaComprobante $haciendaComprobante
     * @return JsonResponse
     */
    public function show(HaciendaComprobante $haciendaComprobante): JsonResponse
    {
        try {
            // Detalle conciso del comprobante para vistas de administrador
            // o conciliación. Decodifica la respuesta de Hacienda para
            // facilitar inspección desde el cliente.
            return $this->success([
                'id' => $haciendaComprobante->id,
                'comprobante_id' => $haciendaComprobante->comprobante_id,
                'empresa_id' => $haciendaComprobante->empresa_id,
                'clave' => $haciendaComprobante->clave,
                'tipo_comprobante' => $haciendaComprobante->tipo_comprobante,
                'estado' => $haciendaComprobante->estado,
                'created_at' => $haciendaComprobante->created_at?->toIso8601String(),
                'respuesta_hacienda' => $haciendaComprobante->respuesta_hacienda ? json_decode($haciendaComprobante->respuesta_hacienda) : null,
            ]);
        } catch (\Exception $e) {
            return $this->error(config('app.debug') ? $e->getMessage() : 'Error interno del servidor', 500);
        }
    }
}
