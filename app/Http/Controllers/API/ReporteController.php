<?php

namespace App\Http\Controllers\API;

use App\DTOs\API\ReportFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReportFilterRequest;
use App\Services\ReportExportService;
use App\Services\ReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
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
    public function invalidarCache(): JsonResponse
    {
        /** @var \App\Models\Usuario $usuario */
        $usuario = request()->user();
        $this->reportingService->invalidarCache((int) $usuario->empresa_id);

        return $this->successResponse(message: 'Cache de reportes invalidado');
    }
}
