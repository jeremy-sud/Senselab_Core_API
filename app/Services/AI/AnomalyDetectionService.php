<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Venta;
use App\Models\MovimientoCajaChica;
use App\Models\AsientoContable;
use App\Models\DetalleVenta;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Detección de Anomalías Financieras
 *
 * Detecta automáticamente:
 * - Transacciones sospechosas
 * - Patrones de fraude potencial
 * - Errores contables
 * - Descuentos excesivos
 * - Movimientos de caja atípicos
 *
 * Usa análisis estadístico (Z-Score, IQR) + IA para explicaciones
 *
 * Desarrollado por Sistemas Ursol S.A.
 */
class AnomalyDetectionService
{
    protected AIServiceInterface $aiService;
    protected int $empresaId;

    /**
     * Umbrales de detección
     * @var array<string, float|int>
     */
    protected array $thresholds = [
        'z_score' => 3.0,           // Desviaciones estándar para considerar anomalía
        'iqr_multiplier' => 1.5,    // Multiplicador IQR para outliers
        'min_samples' => 30,        // Mínimo de muestras para análisis estadístico
        'discount_max_percent' => 30, // Descuento máximo normal (%)
        'unusual_hour_start' => 22,  // Hora inicio inusual
        'unusual_hour_end' => 6,     // Hora fin inusual
    ];

    public function __construct(?AIServiceInterface $aiService = null)
    {
        if ($aiService) {
            $this->aiService = $aiService;
        } elseif (!empty(config('gemini.api_key'))) {
            $this->aiService = app(GeminiService::class);
        } else {
            $this->aiService = app(OpenAIService::class);
        }
    }

    public function setEmpresa(int $empresaId): self
    {
        $this->empresaId = $empresaId;
        return $this;
    }

    /**
     * Análisis completo de anomalías
     *
     * @return array<string, mixed>
     */
    public function runFullAnalysis(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        $anomalies = [
            'ventas' => $this->detectSalesAnomalies($startDate),
            'descuentos' => $this->detectDiscountAnomalies($startDate),
            'caja_chica' => $this->detectCashAnomalies($startDate),
            'horarios' => $this->detectUnusualHours($startDate),
            'patrones' => $this->detectSuspiciousPatterns($startDate),
        ];

        // Calcular resumen
        $totalAnomalies = 0;
        $criticalCount = 0;
        $allAnomalies = [];

        foreach ($anomalies as $category => $data) {
            if (isset($data['anomalies'])) {
                foreach ($data['anomalies'] as $anomaly) {
                    $totalAnomalies++;
                    $allAnomalies[] = array_merge($anomaly, ['categoria' => $category]);
                    if (($anomaly['severidad'] ?? '') === 'critica') {
                        $criticalCount++;
                    }
                }
            }
        }

        // Ordenar por severidad
        usort($allAnomalies, function ($a, $b) {
            $order = ['critica' => 1, 'alta' => 2, 'media' => 3, 'baja' => 4];
            return ($order[$a['severidad'] ?? 'baja'] ?? 5) <=> ($order[$b['severidad'] ?? 'baja'] ?? 5);
        });

        return [
            'success' => true,
            'periodo' => [
                'inicio' => $startDate->format('Y-m-d'),
                'fin' => Carbon::now()->format('Y-m-d'),
                'dias' => $days,
            ],
            'resumen' => [
                'total_anomalias' => $totalAnomalies,
                'criticas' => $criticalCount,
                'por_categoria' => [
                    'ventas' => count($anomalies['ventas']['anomalies'] ?? []),
                    'descuentos' => count($anomalies['descuentos']['anomalies'] ?? []),
                    'caja_chica' => count($anomalies['caja_chica']['anomalies'] ?? []),
                    'horarios' => count($anomalies['horarios']['anomalies'] ?? []),
                    'patrones' => count($anomalies['patrones']['anomalies'] ?? []),
                ],
            ],
            'anomalias' => array_slice($allAnomalies, 0, 50), // Top 50
            'detalles_por_categoria' => $anomalies,
            'generado_en' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Detectar anomalías en ventas (montos inusuales)
     *
     * @return array<string, mixed>
     */
    public function detectSalesAnomalies(Carbon $startDate): array
    {
        $ventas = DB::table('ventas')
            ->where('empresa_id', $this->empresaId)
            ->where('fecha', '>=', $startDate)
            ->whereNull('deleted_at')
            ->select(['id', 'fecha', 'total', 'cliente_id', 'usuario_id', 'created_at'])
            ->get();

        if ($ventas->count() < $this->thresholds['min_samples']) {
            return [
                'success' => true,
                'anomalies' => [],
                'message' => 'Datos insuficientes para análisis estadístico',
            ];
        }

        $totales = $ventas->pluck('total')->map(fn($v) => (float)$v)->toArray();
        $stats = $this->calculateStats($totales);
        $anomalies = [];

        foreach ($ventas as $venta) {
            $total = (float)$venta->total;
            $zScore = $this->calculateZScore($total, $stats['mean'], $stats['std']);

            if (abs($zScore) >= $this->thresholds['z_score']) {
                $severidad = abs($zScore) >= 4 ? 'critica' : (abs($zScore) >= 3.5 ? 'alta' : 'media');

                $anomalies[] = [
                    'tipo' => 'venta_monto_inusual',
                    'entidad' => 'venta',
                    'entidad_id' => $venta->id,
                    'fecha' => $venta->fecha,
                    'valor' => $total,
                    'valor_esperado' => round($stats['mean'], 2),
                    'desviacion' => round($zScore, 2),
                    'severidad' => $severidad,
                    'descripcion' => $zScore > 0
                        ? "Venta inusualmente alta: ₡" . number_format($total, 2) . " (promedio: ₡" . number_format($stats['mean'], 2) . ")"
                        : "Venta inusualmente baja: ₡" . number_format($total, 2) . " (promedio: ₡" . number_format($stats['mean'], 2) . ")",
                    'usuario_id' => $venta->usuario_id,
                ];
            }
        }

        return [
            'success' => true,
            'estadisticas' => [
                'promedio' => round($stats['mean'], 2),
                'desviacion_estandar' => round($stats['std'], 2),
                'minimo' => $stats['min'],
                'maximo' => $stats['max'],
                'total_ventas' => $ventas->count(),
            ],
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Detectar descuentos excesivos
     *
     * @return array<string, mixed>
     */
    public function detectDiscountAnomalies(Carbon $startDate): array
    {
        $detalles = DB::table('detalle_ventas as dv')
            ->join('ventas as v', 'dv.venta_id', '=', 'v.id')
            ->where('v.empresa_id', $this->empresaId)
            ->where('v.fecha', '>=', $startDate)
            ->whereNotNull('dv.descuento')
            ->where('dv.descuento', '>', 0)
            ->select([
                'dv.id',
                'dv.venta_id',
                'dv.producto_id',
                'dv.cantidad',
                'dv.precio_unitario',
                'dv.descuento',
                'dv.subtotal',
                'v.fecha',
                'v.usuario_id',
            ])
            ->get();

        $anomalies = [];

        foreach ($detalles as $detalle) {
            $precioSinDescuento = (float)$detalle->precio_unitario * (float)$detalle->cantidad;
            $descuento = (float)$detalle->descuento;

            if ($precioSinDescuento > 0) {
                $porcentajeDescuento = ($descuento / $precioSinDescuento) * 100;

                if ($porcentajeDescuento > $this->thresholds['discount_max_percent']) {
                    $severidad = $porcentajeDescuento > 50 ? 'critica' : ($porcentajeDescuento > 40 ? 'alta' : 'media');

                    $anomalies[] = [
                        'tipo' => 'descuento_excesivo',
                        'entidad' => 'detalle_venta',
                        'entidad_id' => $detalle->id,
                        'venta_id' => $detalle->venta_id,
                        'producto_id' => $detalle->producto_id,
                        'fecha' => $detalle->fecha,
                        'valor' => round($porcentajeDescuento, 2),
                        'valor_esperado' => $this->thresholds['discount_max_percent'],
                        'monto_descuento' => $descuento,
                        'severidad' => $severidad,
                        'descripcion' => "Descuento del " . round($porcentajeDescuento, 1) . "% (máximo esperado: {$this->thresholds['discount_max_percent']}%)",
                        'usuario_id' => $detalle->usuario_id,
                    ];
                }
            }
        }

        return [
            'success' => true,
            'total_con_descuento' => $detalles->count(),
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Detectar movimientos de caja chica atípicos
     *
     * @return array<string, mixed>
     */
    public function detectCashAnomalies(Carbon $startDate): array
    {
        $movimientos = DB::table('movimientos_caja_chica')
            ->where('empresa_id', $this->empresaId)
            ->where('fecha', '>=', $startDate)
            ->select(['id', 'fecha', 'monto', 'tipo', 'concepto', 'usuario_id'])
            ->get();

        if ($movimientos->count() < 10) {
            return [
                'success' => true,
                'anomalies' => [],
                'message' => 'Datos insuficientes',
            ];
        }

        $montos = $movimientos->pluck('monto')->map(fn($v) => abs((float)$v))->toArray();
        $stats = $this->calculateStats($montos);
        $anomalies = [];

        foreach ($movimientos as $mov) {
            $monto = abs((float)$mov->monto);
            $zScore = $this->calculateZScore($monto, $stats['mean'], $stats['std']);

            if (abs($zScore) >= $this->thresholds['z_score']) {
                $anomalies[] = [
                    'tipo' => 'caja_chica_monto_inusual',
                    'entidad' => 'movimiento_caja_chica',
                    'entidad_id' => $mov->id,
                    'fecha' => $mov->fecha,
                    'valor' => $monto,
                    'valor_esperado' => round($stats['mean'], 2),
                    'desviacion' => round($zScore, 2),
                    'severidad' => abs($zScore) >= 4 ? 'alta' : 'media',
                    'descripcion' => "Movimiento de caja chica inusual: ₡" . number_format($monto, 2),
                    'concepto' => $mov->concepto,
                    'usuario_id' => $mov->usuario_id,
                ];
            }
        }

        return [
            'success' => true,
            'estadisticas' => [
                'promedio' => round($stats['mean'], 2),
                'total_movimientos' => $movimientos->count(),
            ],
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Detectar transacciones en horarios inusuales
     *
     * @return array<string, mixed>
     */
    public function detectUnusualHours(Carbon $startDate): array
    {
        $ventas = DB::table('ventas')
            ->where('empresa_id', $this->empresaId)
            ->where('fecha', '>=', $startDate)
            ->whereRaw('HOUR(created_at) >= ? OR HOUR(created_at) < ?', [
                $this->thresholds['unusual_hour_start'],
                $this->thresholds['unusual_hour_end'],
            ])
            ->select(['id', 'fecha', 'total', 'usuario_id', 'created_at'])
            ->get();

        $anomalies = [];

        foreach ($ventas as $venta) {
            $hora = Carbon::parse($venta->created_at)->format('H:i');

            $anomalies[] = [
                'tipo' => 'horario_inusual',
                'entidad' => 'venta',
                'entidad_id' => $venta->id,
                'fecha' => $venta->fecha,
                'hora' => $hora,
                'valor' => (float)$venta->total,
                'severidad' => 'media',
                'descripcion' => "Venta registrada a las {$hora} (fuera de horario normal)",
                'usuario_id' => $venta->usuario_id,
            ];
        }

        return [
            'success' => true,
            'horario_inusual' => "{$this->thresholds['unusual_hour_start']}:00 - {$this->thresholds['unusual_hour_end']}:00",
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Detectar patrones sospechosos
     *
     * @return array<string, mixed>
     */
    public function detectSuspiciousPatterns(Carbon $startDate): array
    {
        $anomalies = [];

        // Patrón 1: Ventas duplicadas (mismo cliente, mismo monto, mismo día)
        $duplicadas = DB::table('ventas')
            ->where('empresa_id', $this->empresaId)
            ->where('fecha', '>=', $startDate)
            ->whereNotNull('cliente_id')
            ->groupBy('cliente_id', 'fecha', 'total')
            ->havingRaw('COUNT(*) > 1')
            ->select(['cliente_id', 'fecha', 'total', DB::raw('COUNT(*) as cantidad')])
            ->get();

        foreach ($duplicadas as $dup) {
            $anomalies[] = [
                'tipo' => 'ventas_duplicadas',
                'entidad' => 'venta',
                'cliente_id' => $dup->cliente_id,
                'fecha' => $dup->fecha,
                'valor' => (float)$dup->total,
                'cantidad' => $dup->cantidad,
                'severidad' => $dup->cantidad > 3 ? 'alta' : 'media',
                'descripcion' => "{$dup->cantidad} ventas idénticas al mismo cliente (₡" . number_format($dup->total, 2) . ")",
            ];
        }

        // Patrón 2: Anulaciones frecuentes por usuario
        $anulaciones = DB::table('ventas')
            ->where('empresa_id', $this->empresaId)
            ->where('fecha', '>=', $startDate)
            ->where('estado', 'anulada')
            ->groupBy('usuario_id')
            ->havingRaw('COUNT(*) > 5')
            ->select(['usuario_id', DB::raw('COUNT(*) as total_anulaciones')])
            ->get();

        foreach ($anulaciones as $anul) {
            $anomalies[] = [
                'tipo' => 'anulaciones_frecuentes',
                'entidad' => 'usuario',
                'usuario_id' => $anul->usuario_id,
                'valor' => $anul->total_anulaciones,
                'severidad' => $anul->total_anulaciones > 10 ? 'alta' : 'media',
                'descripcion' => "Usuario con {$anul->total_anulaciones} ventas anuladas en el período",
            ];
        }

        // Patrón 3: Secuencias de montos redondos (posible fraude)
        $montosRedondos = DB::table('ventas')
            ->where('empresa_id', $this->empresaId)
            ->where('fecha', '>=', $startDate)
            ->whereRaw('MOD(total, 1000) = 0')
            ->where('total', '>', 0)
            ->groupBy('usuario_id')
            ->havingRaw('COUNT(*) > 10')
            ->select(['usuario_id', DB::raw('COUNT(*) as cantidad_redondos')])
            ->get();

        foreach ($montosRedondos as $redondo) {
            $anomalies[] = [
                'tipo' => 'montos_redondos_frecuentes',
                'entidad' => 'usuario',
                'usuario_id' => $redondo->usuario_id,
                'valor' => $redondo->cantidad_redondos,
                'severidad' => 'media',
                'descripcion' => "Usuario con {$redondo->cantidad_redondos} ventas de montos redondos (múltiplos de ₡1000)",
            ];
        }

        return [
            'success' => true,
            'anomalies' => $anomalies,
        ];
    }

    /**
     * Obtener explicación IA para una anomalía
     *
     * @param array<string, mixed> $anomaly
     * @return array<string, mixed>
     */
    public function explainAnomaly(array $anomaly): array
    {
        $prompt = <<<PROMPT
Eres un auditor financiero experto. Analiza la siguiente anomalía detectada en un sistema ERP de Costa Rica y proporciona:

1. Posibles causas legítimas
2. Indicadores de alerta (red flags)
3. Acciones recomendadas
4. Nivel de urgencia de investigación

Anomalía detectada:
- Tipo: {$anomaly['tipo']}
- Descripción: {$anomaly['descripcion']}
- Valor: {$anomaly['valor']}
- Severidad: {$anomaly['severidad']}
- Fecha: {$anomaly['fecha']}

Responde en formato estructurado y conciso.
PROMPT;

        $result = $this->aiService->chat($prompt, [], [
            'temperature' => 0.3,
            'max_tokens' => 1024,
        ]);

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Error al generar explicación',
            ];
        }

        return [
            'success' => true,
            'anomalia' => $anomaly,
            'explicacion' => $result['content'] ?? $result['message'] ?? '',
            'provider' => $result['provider'] ?? 'unknown',
        ];
    }

    // ========== MÉTODOS AUXILIARES ==========

    /**
     * @param array<int, float|int> $values
     * @return array<string, float|int>
     */
    protected function calculateStats(array $values): array
    {
        $count = count($values);
        if ($count === 0) {
            return ['mean' => 0, 'std' => 0, 'min' => 0, 'max' => 0];
        }

        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $values)) / $count;
        $std = sqrt($variance);

        return [
            'mean' => $mean,
            'std' => $std,
            'min' => min($values),
            'max' => max($values),
            'count' => $count,
        ];
    }

    protected function calculateZScore(float $value, float $mean, float $std): float
    {
        if ($std == 0) {
            return 0;
        }
        return ($value - $mean) / $std;
    }
}

