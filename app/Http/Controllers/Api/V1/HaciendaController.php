<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ComprobanteElectronicoFe;
use App\Models\HaciendaComprobante;
use App\Services\Hacienda\HaciendaApiClient;
use App\Services\Hacienda\Xml\FirmaDigitalService;
use App\Services\Hacienda\Xml\XmlComprobanteBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

/**
 * API Controller - Integración con Hacienda Costa Rica
 *
 * Endpoints para envío, validación y seguimiento de comprobantes electrónicos
 * hacia el sistema del Ministerio de Hacienda.
 *
 * @OA\Tag(
 *     name="Hacienda - Facturación Electrónica",
 *     description="Integración con el sistema de Hacienda Costa Rica para comprobantes electrónicos v4.4"
 * )
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
     * @OA\Post(
     *     path="/api/v1/hacienda/generar",
     *     summary="Generar comprobante electrónico",
     *     description="Genera un comprobante electrónico para envío a Hacienda a partir de un comprobante existente",
     *     operationId="generarComprobanteHacienda",
     *     tags={"Hacienda - Facturación Electrónica"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"comprobante_id"},
     *             @OA\Property(property="comprobante_id", type="integer", example=1, description="ID del comprobante electrónico FE"),
     *             @OA\Property(property="tipo", type="string", enum={"01","03","04","05","07"}, example="01", description="Tipo de comprobante: 01=FE, 03=NC, 04=ND, 05=Tiquete, 07=MR")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comprobante generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="clave", type="string", example="50601042600310123456700100001010000000001199999999"),
     *                 @OA\Property(property="estado", type="string", example="pending"),
     *                 @OA\Property(property="tipo", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Comprobante ya existe previamente"),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Post(
     *     path="/api/v1/hacienda/{id}/generar-xml",
     *     summary="Generar XML del comprobante",
     *     description="Genera el XML conforme a la especificación DGT v4.4 para un comprobante de Hacienda",
     *     operationId="generarXmlHacienda",
     *     tags={"Hacienda - Facturación Electrónica"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del comprobante Hacienda",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="XML generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="xml_preview", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Comprobante no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Post(
     *     path="/api/v1/hacienda/{id}/firmar",
     *     summary="Firmar comprobante con certificado digital",
     *     description="Firma el XML del comprobante con certificado digital XAdES-EPES según norma DGT-R-000-2024",
     *     operationId="firmarComprobanteHacienda",
     *     tags={"Hacienda - Facturación Electrónica"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del comprobante Hacienda",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"certificado_id"},
     *             @OA\Property(property="certificado_id", type="integer", example=1, description="ID del certificado digital .p12")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comprobante firmado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="estado", type="string", example="signed")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Comprobante no encontrado"),
     *     @OA\Response(response=422, description="XML no generado aún"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Post(
     *     path="/api/v1/hacienda/{id}/enviar",
     *     summary="Enviar comprobante a Hacienda",
     *     description="Envía el comprobante firmado a la API de recepción del Ministerio de Hacienda CR",
     *     operationId="enviarComprobanteHacienda",
     *     tags={"Hacienda - Facturación Electrónica"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del comprobante Hacienda",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comprobante enviado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="clave", type="string"),
     *                 @OA\Property(property="estado", type="string", example="sent"),
     *                 @OA\Property(property="respuesta", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Comprobante no encontrado"),
     *     @OA\Response(response=422, description="Comprobante no está firmado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Get(
     *     path="/api/v1/hacienda/{id}/estado",
     *     summary="Consultar estado en Hacienda",
     *     description="Consulta el estado actual del comprobante en la API de Hacienda y sincroniza el estado local",
     *     operationId="getEstadoHacienda",
     *     tags={"Hacienda - Facturación Electrónica"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del comprobante Hacienda",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="ind-estado", type="string", example="aceptado"),
     *                 @OA\Property(property="clave", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Comprobante no encontrado"),
     *     @OA\Response(response=422, description="No se pudo obtener el estado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Get(
     *     path="/api/v1/hacienda/estadisticas",
     *     summary="Estadísticas de comprobantes",
     *     description="Obtiene estadísticas de comprobantes enviados a Hacienda agrupados por estado",
     *     operationId="estadisticasHacienda",
     *     tags={"Hacienda - Facturación Electrónica"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas obtenidas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total", type="integer", example=150),
     *                 @OA\Property(property="pending", type="integer", example=5),
     *                 @OA\Property(property="signed", type="integer", example=3),
     *                 @OA\Property(property="sent", type="integer", example=10),
     *                 @OA\Property(property="accepted", type="integer", example=120),
     *                 @OA\Property(property="rejected", type="integer", example=12)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Get(
     *     path="/api/v1/hacienda",
     *     summary="Listar comprobantes Hacienda",
     *     description="Lista comprobantes enviados a Hacienda con filtros por estado, tipo, empresa y clave",
     *     operationId="indexHacienda",
     *     tags={"Hacienda - Facturación Electrónica"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="estado", in="query", required=false, description="Filtrar por estado",
     *         @OA\Schema(type="string", enum={"pending","signed","sent","accepted","rejected","error"})
     *     ),
     *     @OA\Parameter(name="tipo", in="query", required=false, description="Filtrar por tipo de comprobante",
     *         @OA\Schema(type="string", enum={"01","03","04","05","07"})
     *     ),
     *     @OA\Parameter(name="empresa_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="clave", in="query", required=false, description="Buscar por clave numérica de 50 dígitos",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", default=15)),
     *     @OA\Response(
     *         response=200,
     *         description="Listado paginado de comprobantes",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
     * @OA\Get(
     *     path="/api/v1/hacienda/{id}",
     *     summary="Detalle de comprobante Hacienda",
     *     description="Obtiene los detalles completos de un comprobante, incluyendo la respuesta de Hacienda",
     *     operationId="showHacienda",
     *     tags={"Hacienda - Facturación Electrónica"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del comprobante Hacienda",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Detalle del comprobante",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="comprobante_id", type="integer"),
     *                 @OA\Property(property="empresa_id", type="integer"),
     *                 @OA\Property(property="clave", type="string"),
     *                 @OA\Property(property="tipo_comprobante", type="string"),
     *                 @OA\Property(property="estado", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="respuesta_hacienda", type="object", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Comprobante no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
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
