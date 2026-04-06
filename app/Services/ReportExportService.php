<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Illuminate\Support\Facades\Storage;

class ReportExportService
{
    /**
     * Exporta datos de reporte a PDF.
     *
     * @param array<string, mixed> $data
     */
    public function exportarPdf(array $data, string $tipoReporte): string
    {
        $viewName = "reports.{$tipoReporte}";
        $filename = $this->generateFilename($tipoReporte, 'pdf');

        $pdf = Pdf::loadView($viewName, ['data' => $data, 'tipo' => $tipoReporte]);
        Storage::disk('local')->put("reports/{$filename}", $pdf->output());

        return $filename;
    }

    /**
     * Exporta datos de reporte a Excel.
     *
     * @param array<string, mixed> $data
     */
    public function exportarExcel(array $data, string $tipoReporte): string
    {
        $filename = $this->generateFilename($tipoReporte, 'xlsx');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(str_replace('_', ' ', ucfirst($tipoReporte)));

        $row = 1;
        $row = $this->writeHeader($sheet, $data, $row);
        $this->writeData($sheet, $data, $row);

        $writer = new Xlsx($spreadsheet);
        $path = Storage::disk('local')->path("reports/{$filename}");
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $writer->save($path);

        return $filename;
    }

    /**
     * Exporta datos de reporte a CSV.
     *
     * @param array<string, mixed> $data
     */
    public function exportarCsv(array $data, string $tipoReporte): string
    {
        $filename = $this->generateFilename($tipoReporte, 'csv');
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $row = 1;
        $row = $this->writeHeader($sheet, $data, $row);
        $this->writeData($sheet, $data, $row);

        $writer = new Csv($spreadsheet);
        $writer->setDelimiter(',');
        $writer->setEnclosure('"');
        $path = Storage::disk('local')->path("reports/{$filename}");
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $writer->save($path);

        return $filename;
    }

    /**
     * Exporta en el formato solicitado.
     *
     * @param array<string, mixed> $data
     */
    public function exportar(array $data, string $tipoReporte, string $formato): string
    {
        return match ($formato) {
            'pdf' => $this->exportarPdf($data, $tipoReporte),
            'excel' => $this->exportarExcel($data, $tipoReporte),
            'csv' => $this->exportarCsv($data, $tipoReporte),
            default => throw new \InvalidArgumentException("Formato no soportado: {$formato}"),
        };
    }

    private function generateFilename(string $tipo, string $extension): string
    {
        $timestamp = now()->format('Y-m-d_His');
        return "reporte_{$tipo}_{$timestamp}.{$extension}";
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param array<string, mixed> $data
     */
    private function writeHeader($sheet, array $data, int $row): int
    {
        $tipo = $data['tipo_reporte'] ?? 'Reporte';
        $sheet->setCellValue("A{$row}", 'Reporte: ' . str_replace('_', ' ', ucfirst($tipo)));
        $row++;

        if (isset($data['periodo'])) {
            /** @var array{inicio: string, fin: string} $periodo */
            $periodo = $data['periodo'];
            $sheet->setCellValue("A{$row}", 'Período: ' . $periodo['inicio'] . ' al ' . $periodo['fin']);
            $row++;
        }

        if (isset($data['fecha_corte'])) {
            $sheet->setCellValue("A{$row}", 'Fecha de corte: ' . $data['fecha_corte']);
            $row++;
        }

        if (isset($data['moneda'])) {
            $sheet->setCellValue("A{$row}", 'Moneda: ' . $data['moneda']);
            $row++;
        }

        $row++; // blank line
        return $row;
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
     * @param array<string, mixed> $data
     */
    private function writeData($sheet, array $data, int $row): void
    {
        // Write sections dynamically
        $sections = ['ingresos', 'costos_venta', 'gastos_operativos', 'activos', 'pasivos', 'capital', 'actividades_operativas'];

        foreach ($sections as $section) {
            if (!isset($data[$section])) {
                continue;
            }

            $sheet->setCellValue("A{$row}", strtoupper(str_replace('_', ' ', $section)));
            $row++;

            /** @var array<string|int, mixed> $sectionData */
            $sectionData = $data[$section];

            if (is_array($sectionData) && !empty($sectionData)) {
                // Check if it's a list of accounts or key-value pairs
                $first = reset($sectionData);
                if (is_array($first) && isset($first['codigo'])) {
                    // Account list
                    $sheet->setCellValue("A{$row}", 'Código');
                    $sheet->setCellValue("B{$row}", 'Cuenta');
                    $sheet->setCellValue("C{$row}", 'Monto');
                    $row++;
                    foreach ($sectionData as $item) {
                        /** @var array{codigo: string, nombre: string, monto?: string, saldo?: string} $item */
                        $sheet->setCellValue("A{$row}", $item['codigo']);
                        $sheet->setCellValue("B{$row}", $item['nombre']);
                        $sheet->setCellValue("C{$row}", $item['monto'] ?? $item['saldo'] ?? '0.00');
                        $row++;
                    }
                } else {
                    // Key-value pairs
                    foreach ($sectionData as $key => $value) {
                        $sheet->setCellValue("A{$row}", str_replace('_', ' ', ucfirst((string) $key)));
                        $sheet->setCellValue("B{$row}", (string) $value);
                        $row++;
                    }
                }
            }

            $row++; // blank line between sections
        }

        // Write totals
        if (isset($data['totales'])) {
            $sheet->setCellValue("A{$row}", 'TOTALES');
            $row++;
            /** @var array<string, string> $totales */
            $totales = $data['totales'];
            foreach ($totales as $key => $value) {
                $sheet->setCellValue("A{$row}", str_replace('_', ' ', ucfirst($key)));
                $sheet->setCellValue("B{$row}", $value);
                $row++;
            }
        }
    }
}
