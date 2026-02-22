<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ComprobanteElectronicoFe;
use App\Models\HaciendaComprobante;
use App\Services\Hacienda\HaciendaIntegrationService;
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
    /**
     * POST /api/v1/hacienda/generar
     * Generar comprobante electrónico para envío a Hacienda
     *
     * @param ComprobanteElectronicoFe $comprobante
     * @param Request $request
     * @return JsonResponse
     */
    public function generar(ComprobanteElectronicoFe $comprobante, Request $request): JsonResponse
    {
        try {
            // Este endpoint crea el registro de `HaciendaComprobante` que
            // representa el flujo de envío de un comprobante a Hacienda.
            // No genera ni firma el XML aquí; solo prepara el registro y
            // calcula la `clave` única. Pasos posteriores (generarXml,
            // firmar, enviar) realizan el resto del flujo.
            $tipo = $request->input('tipo', HaciendaIntegrationService::TYPE_FACTURA);

            $haciendaComprobante = HaciendaIntegrationService::generateComprobante($comprobante, $tipo);

            if (! $haciendaComprobante) {
                return $this->error('No se pudo generar el comprobante electrónico', 422);
            }

            return $this->success([
                'id' => $haciendaComprobante->id,
                'clave' => $haciendaComprobante->clave,
                'estado' => $haciendaComprobante->estado,
                'tipo' => $haciendaComprobante->tipo_comprobante,
            ], 'Comprobante generado exitosamente', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/hacienda/{id}/generar-xml
     * Generar XML del comprobante
     *
     * @param HaciendaComprobante $haciendaComprobante
     * @return JsonResponse
     */
    public function generarXml(HaciendaComprobante $haciendaComprobante): JsonResponse
    {
        try {
            // Genera el XML conforme al esquema y lo guarda en el registro.
            // Retorna una vista previa del XML (primeros 500 caracteres)
            // para facilitar inspección rápida en el cliente.
            if (! HaciendaIntegrationService::generateXml($haciendaComprobante)) {
                return $this->error('No se pudo generar el XML', 422);
            }

            return $this->success([
                'id' => $haciendaComprobante->id,
                'xml_preview' => substr($haciendaComprobante->fresh()->xml_content, 0, 500) . '...',
            ], 'XML generado exitosamente');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/hacienda/{id}/firmar
     * Firmar comprobante con certificado digital XAdES-EPES
     *
     * @param HaciendaComprobante $haciendaComprobante
     * @param Request $request
     * @return JsonResponse
     */
    public function firmar(HaciendaComprobante $haciendaComprobante, Request $request): JsonResponse
    {
        try {
            // Valida que se proporcionen ruta y contraseña del certificado.
            // En entornos reales, la ruta al certificado debería estar
            // protegida y/o almacenada en un HSM. Aquí se acepta ruta local
            // para entornos controlados.
            $request->validate([
                'certificado_ruta' => 'required|string',
                'certificado_password' => 'required|string',
            ]);

            if (! HaciendaIntegrationService::signWithXADES(
                $haciendaComprobante,
                $request->input('certificado_ruta'),
                $request->input('certificado_password')
            )) {
                return $this->error('No se pudo firmar el comprobante', 422);
            }

            return $this->success([
                'id' => $haciendaComprobante->id,
                'estado' => $haciendaComprobante->fresh()->estado,
            ], 'Comprobante firmado exitosamente');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/hacienda/{id}/enviar
     * Enviar comprobante a la API de Hacienda
     *
     * @param HaciendaComprobante $haciendaComprobante
     * @return JsonResponse
     */
    public function enviar(HaciendaComprobante $haciendaComprobante): JsonResponse
    {
        try {
            // Envía el XML firmado a Hacienda usando la URL configurada
            // en `config/hacienda.php`. Se espera que `sendToHacienda`
            // persista la respuesta para auditoría.
            if (! HaciendaIntegrationService::sendToHacienda($haciendaComprobante)) {
                return $this->error('No se pudo enviar el comprobante a Hacienda', 422);
            }

            return $this->success([
                'id' => $haciendaComprobante->id,
                'clave' => $haciendaComprobante->clave,
                'estado' => $haciendaComprobante->fresh()->estado,
            ], 'Comprobante enviado a Hacienda exitosamente');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/hacienda/{id}/estado
     * Consultar estado de comprobante en Hacienda
     *
     * @param HaciendaComprobante $haciendaComprobante
     * @return JsonResponse
     */
    public function getEstado(HaciendaComprobante $haciendaComprobante): JsonResponse
    {
        try {
            // Consulta el estado actual del comprobante en Hacienda y
            // sincroniza el estado local si es necesario.
            $status = HaciendaIntegrationService::getStatus($haciendaComprobante);

            if (! $status) {
                return $this->error('No se pudo obtener el estado', 422);
            }

            return $this->success($status, 'Estado obtenido exitosamente');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
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
            // Devuelve métricas agregadas sobre los comprobantes: totales
            // por estado. Útil para dashboards operativos.
            $stats = HaciendaIntegrationService::getStatistics();

            return $this->success($stats, 'Estadísticas de comprobantes');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
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
            return $this->error($e->getMessage(), 500);
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
            return $this->error($e->getMessage(), 500);
        }
    }
}
