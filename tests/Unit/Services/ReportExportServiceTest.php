<?php

namespace Tests\Unit\Services;

use App\Services\ReportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportExportService();
    }

    protected function tearDown(): void
    {
        // Clean up generated files
        Storage::disk('local')->deleteDirectory('reports');
        parent::tearDown();
    }

    public function test_exportar_excel_genera_archivo(): void
    {
        $data = $this->sampleReportData();

        $filename = $this->service->exportarExcel($data, 'estado_resultados');

        $this->assertStringEndsWith('.xlsx', $filename);
        $this->assertTrue(Storage::disk('local')->exists("reports/{$filename}"));
    }

    public function test_exportar_csv_genera_archivo(): void
    {
        $data = $this->sampleReportData();

        $filename = $this->service->exportarCsv($data, 'flujo_caja');

        $this->assertStringEndsWith('.csv', $filename);
        $this->assertTrue(Storage::disk('local')->exists("reports/{$filename}"));
    }

    public function test_exportar_formato_invalido_lanza_excepcion(): void
    {
        $data = $this->sampleReportData();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Formato no soportado: xml');

        $this->service->exportar($data, 'estado_resultados', 'xml');
    }

    public function test_exportar_via_metodo_general_excel(): void
    {
        $data = $this->sampleReportData();

        $filename = $this->service->exportar($data, 'balance_general', 'excel');

        $this->assertStringEndsWith('.xlsx', $filename);
    }

    public function test_exportar_via_metodo_general_csv(): void
    {
        $data = $this->sampleReportData();

        $filename = $this->service->exportar($data, 'balance_general', 'csv');

        $this->assertStringEndsWith('.csv', $filename);
    }

    public function test_exportar_balance_general_con_secciones(): void
    {
        $data = [
            'tipo_reporte' => 'balance_general',
            'fecha_corte' => '2026-04-06',
            'moneda' => 'CRC',
            'activos' => [
                ['codigo' => '1-01', 'nombre' => 'Caja', 'saldo' => '100000.00'],
            ],
            'pasivos' => [
                ['codigo' => '2-01', 'nombre' => 'Préstamos', 'saldo' => '50000.00'],
            ],
            'capital' => [],
            'totales' => [
                'total_activos' => '100000.00',
                'total_pasivos' => '50000.00',
                'total_capital' => '0.00',
                'diferencia' => '50000.00',
            ],
        ];

        $filename = $this->service->exportarExcel($data, 'balance_general');
        $this->assertStringEndsWith('.xlsx', $filename);
        $this->assertTrue(Storage::disk('local')->exists("reports/{$filename}"));
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleReportData(): array
    {
        return [
            'tipo_reporte' => 'estado_resultados',
            'periodo' => ['inicio' => '2026-01-01', 'fin' => '2026-03-31'],
            'moneda' => 'CRC',
            'ingresos' => [
                ['codigo' => '4-01', 'nombre' => 'Ventas', 'monto' => '500000.00'],
            ],
            'costos_venta' => [],
            'gastos_operativos' => [
                ['codigo' => '5-01', 'nombre' => 'Salarios', 'monto' => '200000.00'],
            ],
            'totales' => [
                'total_ingresos' => '500000.00',
                'total_costos' => '0.00',
                'utilidad_bruta' => '500000.00',
                'total_gastos' => '200000.00',
                'utilidad_neta' => '300000.00',
            ],
        ];
    }
}
