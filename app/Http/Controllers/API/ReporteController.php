<?php

namespace App\Http\Controllers\API;

use App\DTOs\API\ReportFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportExportService;
use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReporteController extends Controller
{
    public function __construct(
        private readonly ReportingService $reportingService,
        private readonly ReportExportService $exportService,
    ) {}

    /**
     * Genera reporte financiero según tipo solicitado.
     * Tipos: estado_resultados, balance_general, flujo_caja
     */
    #[OA\Get(
        path: '/api/reportes/financiero',
        summary: 'Generar reporte financiero',
        description: 'Genera un reporte financiero del tipo solicitado (Estado de Resultados, Balance General, Flujo de Caja). Soporta exportación a JSON, PDF, Excel y CSV. Permite filtrar por fecha, sucursal y moneda.',
        security: [['sanctum' => []]],
        tags: ['Reportes'],
        parameters: [
            new OA\Parameter(name: 'tipo', description: 'Tipo de reporte: estado_resultados, balance_general, flujo_caja', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['estado_resultados', 'balance_general', 'flujo_caja'])),
            new OA\Parameter(name: 'fecha_inicio', description: 'Fecha de inicio del período (Y-m-d)', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'fecha_fin', description: 'Fecha fin del período (Y-m-d)', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sucursal_id', description: 'Filtrar por sucursal', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'moneda', description: 'Moneda del reporte (CRC, USD)', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'CRC')),
            new OA\Parameter(name: 'formato', description: 'Formato de salida', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['json', 'pdf', 'excel', 'csv'], default: 'json')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte generado exitosamente (JSON o descarga de archivo)'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function financiero(ReportFilterRequest $request): JsonResponse|BinaryFileResponse
    {
        $filtro = ReportFilterDTO::fromRequest($request);

        $data = $this->reportingService->generar($filtro);

        if ($filtro->formato !== 'json') {
            $filename = $this->exportService->exportar($data, $filtro->tipoReporte, $filtro->formato);
            $path = Storage::disk('local')->path("reports/{$filename}");

            return response()->download($path, $filename)->deleteFileAfterSend(true);
        }

        return $this->successResponse($data, 'Reporte generado exitosamente');
    }

    /**
     * Lista los tipos de reportes disponibles.
     */
    #[OA\Get(
        path: '/api/reportes/tipos',
        summary: 'Tipos de reportes disponibles',
        description: 'Retorna la lista de tipos de reportes financieros disponibles.',
        security: [['sanctum' => []]],
        tags: ['Reportes'],
        responses: [
            new OA\Response(response: 200, description: 'Lista de tipos de reportes'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function tiposDisponibles(): JsonResponse
    {
        return $this->successResponse([
            ['tipo' => 'estado_resultados', 'nombre' => 'Estado de Resultados (P&L)'],
            ['tipo' => 'balance_general', 'nombre' => 'Balance General'],
            ['tipo' => 'flujo_caja', 'nombre' => 'Flujo de Caja'],
        ]);
    }

    /**
     * Invalida cache de reportes para el tenant actual.
     */
    #[OA\Post(
        path: '/api/reportes/invalidar-cache',
        summary: 'Invalidar cache de reportes',
        description: 'Invalida la cache de reportes financieros para forzar regeneración en la próxima consulta.',
        security: [['sanctum' => []]],
        tags: ['Reportes'],
        responses: [
            new OA\Response(response: 200, description: 'Cache de reportes invalidado'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function invalidarCache(): JsonResponse
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = request()->user();
        $this->reportingService->invalidarCache((int) $usuario->empresa_id);

        return $this->successResponse(message: 'Cache de reportes invalidado');
    }
}
