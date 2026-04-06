<?php

namespace App\Jobs;

use App\DTOs\API\ReportFilterDTO;
use App\Mail\ReporteProgramadoMail;
use App\Models\ReporteProgramado;
use App\Services\ReportExportService;
use App\Services\ReportingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class DispatchScheduledReportsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 600;
    /** @var array<int, int> */
    public array $backoff = [60, 120, 300];

    public function __construct()
    {
        $this->onQueue('reports');
    }

    public function handle(ReportingService $reportingService, ReportExportService $exportService): void
    {
        $reportes = ReporteProgramado::pendientesEjecucion()->get();

        foreach ($reportes as $reporte) {
            try {
                $this->procesarReporte($reporte, $reportingService, $exportService);
            } catch (\Throwable $e) {
                Log::error('DispatchScheduledReportsJob: Error procesando reporte', [
                    'reporte_id' => $reporte->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function procesarReporte(
        ReporteProgramado $reporte,
        ReportingService $reportingService,
        ReportExportService $exportService,
    ): void {
        /** @var array<string, mixed> $filtrosConfig */
        $filtrosConfig = $reporte->filtros ?? [];

        $filtro = new ReportFilterDTO(
            empresaId: (int) $reporte->empresa_id,
            tipoReporte: $reporte->tipo_reporte,
            fechaInicio: (string) ($filtrosConfig['fecha_inicio'] ?? now()->startOfMonth()->toDateString()),
            fechaFin: (string) ($filtrosConfig['fecha_fin'] ?? now()->toDateString()),
            sucursalId: isset($filtrosConfig['sucursal_id']) ? (int) $filtrosConfig['sucursal_id'] : null,
            moneda: (string) ($filtrosConfig['moneda'] ?? 'CRC'),
            formato: $reporte->formato,
        );

        $data = $reportingService->generar($filtro);
        $filename = $exportService->exportar($data, $filtro->tipoReporte, $filtro->formato);
        $filePath = Storage::disk('local')->path("reports/{$filename}");

        /** @var string[] $destinatarios */
        $destinatarios = $reporte->destinatarios;

        foreach ($destinatarios as $email) {
            Mail::to($email)->send(new ReporteProgramadoMail(
                nombreReporte: $reporte->nombre,
                tipoReporte: $reporte->tipo_reporte,
                filePath: $filePath,
                fileName: $filename,
            ));
        }

        // Clean up file after sending
        Storage::disk('local')->delete("reports/{$filename}");

        // Update execution timestamps
        $reporte->update([
            'ultima_ejecucion' => now(),
            'proxima_ejecucion' => $this->calcularProximaEjecucion($reporte),
        ]);

        Log::info('DispatchScheduledReportsJob: Reporte enviado', [
            'reporte_id' => $reporte->id,
            'destinatarios' => count($destinatarios),
        ]);
    }

    private function calcularProximaEjecucion(ReporteProgramado $reporte): \Carbon\Carbon
    {
        $hora = $reporte->hora_envio ?? '07:00';

        return match ($reporte->frecuencia) {
            'diario' => now()->addDay()->setTimeFromTimeString($hora),
            'semanal' => now()->addWeek()->setTimeFromTimeString($hora),
            'mensual' => now()->addMonth()->setTimeFromTimeString($hora),
            default => now()->addDay()->setTimeFromTimeString($hora),
        };
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DispatchScheduledReportsJob: Job falló permanentemente', [
            'error' => $exception->getMessage(),
        ]);
    }
}
