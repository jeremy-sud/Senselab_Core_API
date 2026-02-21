<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Venta;
use App\Models\Empresa;

/**
 * Job para generar reportes PDF de forma asíncrona
 * Sprint 8.4 - Queue Jobs
 */
class GeneratePdfReportJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 300; // 5 minutos
    public $backoff = [60, 120, 300]; // Retry delays

    /**
     * Create a new job instance.
     */
    /**
     * @param array<string, mixed> $filters
     */
    public function __construct(
        public string $reportType,
        public int $empresaId,
        public array $filters = [],
        public ?int $userId = null
    ) {
        $this->onQueue('reports');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('GeneratePdfReportJob: Iniciando generación', [
                'report_type' => $this->reportType,
                'empresa_id' => $this->empresaId,
                'filters' => $this->filters,
                'user_id' => $this->userId,
            ]);

            $empresa = Empresa::findOrFail($this->empresaId);
            $filename = $this->generateFilename();
            
            // Generar PDF según tipo de reporte
            $pdf = match($this->reportType) {
                'ventas' => $this->generateVentasReport($empresa),
                'inventario' => $this->generateInventarioReport($empresa),
                'cuentas_cobrar' => $this->generateCuentasCobrarReport($empresa),
                'nomina' => $this->generateNominaReport($empresa),
                default => throw new \InvalidArgumentException("Tipo de reporte no soportado: {$this->reportType}")
            };

            // Guardar PDF en storage
            Storage::disk('local')->put("reports/{$filename}", $pdf->output());

            Log::info('GeneratePdfReportJob: PDF generado exitosamente', [
                'filename' => $filename,
                'size' => Storage::disk('local')->size("reports/{$filename}"),
            ]);

            if ($this->userId) {
                $user = \App\Models\Usuario::find($this->userId);
                if ($user && $user->email) {
                    dispatch(new SendEmailJob(
                        to: $user->email,
                        subject: "Reporte de {$this->reportType} generado",
                        view: 'emails.reports.ready',
                        data: [
                            'user_name' => $user->nombre,
                            'report_type' => $this->reportType,
                            'filename' => $filename,
                            'download_url' => url("/api/reports/download/{$filename}")
                        ]
                    ));
                }
            }

        } catch (\Exception $e) {
            Log::error('GeneratePdfReportJob: Error generando PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function generateFilename(): string
    {
        $timestamp = now()->format('Y-m-d_His');
        return "reporte_{$this->reportType}_{$this->empresaId}_{$timestamp}.pdf";
    }

    /**
     * @return \Barryvdh\DomPDF\PDF
     */
    protected function generateVentasReport(Empresa $empresa)
    {
        $ventas = Venta::where('empresa_id', $empresa->id)
            ->whereBetween('fecha_venta', [
                $this->filters['fecha_inicio'] ?? now()->subMonth(),
                $this->filters['fecha_fin'] ?? now()
            ])
            ->with(['cliente', 'detalles.producto'])
            ->get();

        return Pdf::loadView('reports.ventas', [
            'empresa' => $empresa,
            'ventas' => $ventas,
            'filters' => $this->filters,
        ]);
    }

    /**
     * @return \Barryvdh\DomPDF\PDF
     */
    protected function generateInventarioReport(Empresa $empresa)
    {
        $productos = \App\Models\Producto::where('empresa_id', $empresa->id)
            ->when(isset($this->filters['categoria_id']), function ($query) {
                $query->where('categoria_id', $this->filters['categoria_id']);
            })
            ->with(['categoria', 'unidadMedida'])
            ->get();

        return Pdf::loadView('reports.inventario', [
            'empresa' => $empresa,
            'productos' => $productos,
            'filters' => $this->filters,
        ]);
    }

    /**
     * @return \Barryvdh\DomPDF\PDF
     */
    protected function generateCuentasCobrarReport(Empresa $empresa)
    {
        $cuentas = \App\Models\CuentaCobrar::where('empresa_id', $empresa->id)
            ->when(isset($this->filters['estado']), function ($query) {
                $query->where('estado', $this->filters['estado']);
            })
            ->with(['cliente', 'venta'])
            ->get();

        return Pdf::loadView('reports.cuentas_cobrar', [
            'empresa' => $empresa,
            'cuentas' => $cuentas,
            'filters' => $this->filters,
        ]);
    }

    /**
     * @return \Barryvdh\DomPDF\PDF
     */
    protected function generateNominaReport(Empresa $empresa)
    {
        $periodos = \App\Models\PeriodoNomina::where('empresa_id', $empresa->id)
            ->when(isset($this->filters['fecha_inicio']), function ($query) {
                $query->where('fecha_inicio', '>=', $this->filters['fecha_inicio']);
            })
            ->when(isset($this->filters['fecha_fin']), function ($query) {
                $query->where('fecha_fin', '<=', $this->filters['fecha_fin']);
            })
            ->with(['pagos.empleado'])
            ->get();

        return Pdf::loadView('reports.nomina', [
            'empresa' => $empresa,
            'periodos' => $periodos,
            'filters' => $this->filters,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('GeneratePdfReportJob: Job failed permanently', [
            'report_type' => $this->reportType,
            'empresa_id' => $this->empresaId,
            'error' => $exception->getMessage(),
        ]);

        if ($this->userId) {
            $user = \App\Models\Usuario::find($this->userId);
            if ($user && $user->email) {
                dispatch(new SendEmailJob(
                    to: $user->email,
                    subject: "Error generando reporte de {$this->reportType}",
                    view: 'emails.reports.failed',
                    data: [
                        'user_name' => $user->nombre,
                        'report_type' => $this->reportType,
                        'error_message' => 'Ocurrió un error al generar el reporte. Por favor, intente nuevamente más tarde.'
                    ]
                ));
            }
        }
    }
}
