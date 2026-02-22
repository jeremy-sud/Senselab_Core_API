<?php

declare(strict_types=1);

namespace App\Services\AI;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Servicio de Credit Scoring para Clientes
 *
 * Calcula automáticamente:
 * - Score de riesgo crediticio (0-100)
 * - Probabilidad de mora
 * - Límite de crédito recomendado
 * - Clasificación de riesgo
 *
 * Basado en historial de pagos, compras y comportamiento
 *
 * Desarrollado por Sistemas Ursol S.A.
 */
class CreditScoringService
{
    protected int $empresaId;

    /**
     * Pesos para el cálculo del score
     * @var array<string, float>
     */
    protected array $weights = [
        'historial_pagos' => 0.35,      // 35% - Más importante
        'antiguedad' => 0.15,            // 15%
        'volumen_compras' => 0.15,       // 15%
        'frecuencia_compras' => 0.10,    // 10%
        'monto_promedio' => 0.10,        // 10%
        'deuda_actual' => 0.15,          // 15%
    ];

    /**
     * Rangos de clasificación
     * @var array<string, array<string, mixed>>
     */
    protected array $riskLevels = [
        'excelente' => ['min' => 80, 'max' => 100, 'color' => 'green'],
        'bueno' => ['min' => 60, 'max' => 79, 'color' => 'blue'],
        'regular' => ['min' => 40, 'max' => 59, 'color' => 'yellow'],
        'riesgoso' => ['min' => 20, 'max' => 39, 'color' => 'orange'],
        'alto_riesgo' => ['min' => 0, 'max' => 19, 'color' => 'red'],
    ];

    public function setEmpresa(int $empresaId): self
    {
        $this->empresaId = $empresaId;
        return $this;
    }

    /**
     * Calcular score crediticio de un cliente
     *
     * @return array<string, mixed>
     */
    public function calculateScore(int $clienteId): array
    {
        $cacheKey = "credit_score:{$this->empresaId}:{$clienteId}";

        // Cache por 24 horas
        /** @var array<string, mixed> $result */
        // @phpstan-ignore-next-line
        $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, 86400, function () use ($clienteId): array {
            $cliente = $this->getClienteData($clienteId);

            if (!$cliente) {
                return [
                    'success' => false,
                    'error' => 'Cliente no encontrado',
                ];
            }

            // Obtener métricas
            $metricas = $this->calculateMetrics($clienteId);

            // Calcular scores individuales
            $scores = [
                'historial_pagos' => $this->scoreHistorialPagos($metricas),
                'antiguedad' => $this->scoreAntiguedad($metricas),
                'volumen_compras' => $this->scoreVolumenCompras($metricas),
                'frecuencia_compras' => $this->scoreFrecuenciaCompras($metricas),
                'monto_promedio' => $this->scoreMontoPromedio($metricas),
                'deuda_actual' => $this->scoreDeudaActual($metricas),
            ];

            // Calcular score final ponderado
            $scoreFinal = 0;
            foreach ($scores as $key => $score) {
                $scoreFinal += $score * $this->weights[$key];
            }
            $scoreFinal = round($scoreFinal, 1);

            // Determinar nivel de riesgo
            $nivelRiesgo = $this->determineRiskLevel($scoreFinal);

            // Calcular límite de crédito recomendado
            $limiteCredito = $this->calculateCreditLimit($scoreFinal, $metricas);

            // Probabilidad de mora
            $probMora = $this->calculateDefaultProbability($scoreFinal, $metricas);

            return [
                'success' => true,
                'cliente_id' => $clienteId,
                'cliente_nombre' => $cliente->nombre,
                'score' => $scoreFinal,
                'nivel_riesgo' => $nivelRiesgo['nivel'],
                'color' => $nivelRiesgo['color'],
                'probabilidad_mora' => round($probMora, 1),
                'limite_credito_recomendado' => $limiteCredito,
                'scores_detalle' => $scores,
                'metricas' => [
                    'dias_como_cliente' => $metricas['dias_cliente'],
                    'total_compras' => $metricas['total_compras'],
                    'monto_total_historico' => round($metricas['monto_total'], 2),
                    'promedio_compra' => round($metricas['promedio_compra'], 2),
                    'deuda_actual' => round($metricas['deuda_actual'], 2),
                    'facturas_vencidas' => $metricas['facturas_vencidas'],
                    'dias_mora_promedio' => round($metricas['dias_mora_promedio'], 1),
                    'pagos_puntuales_pct' => round($metricas['pct_pagos_puntuales'], 1),
                ],
                'recomendaciones' => $this->generateRecommendations($scoreFinal, $metricas),
                'calculado_en' => Carbon::now()->toIso8601String(),
            ];
        });

        return $result;
    }

    /**
     * Calcular scores para todos los clientes
     *
     * @return array<string, mixed>
     */
    public function calculateAllScores(int $limit = 100): array
    {
        $clientes = DB::table('clientes')
            ->where('empresa_id', $this->empresaId)
            ->where('activo', true)
            ->limit($limit)
            ->pluck('id');

        $results = [];
        $distribution = [
            'excelente' => 0,
            'bueno' => 0,
            'regular' => 0,
            'riesgoso' => 0,
            'alto_riesgo' => 0,
        ];

        foreach ($clientes as $clienteId) {
            $score = $this->calculateScore($clienteId);
            if ($score['success']) {
                $results[] = [
                    'cliente_id' => $clienteId,
                    'nombre' => $score['cliente_nombre'],
                    'score' => $score['score'],
                    'nivel' => $score['nivel_riesgo'],
                    'limite_credito' => $score['limite_credito_recomendado'],
                    'deuda_actual' => $score['metricas']['deuda_actual'],
                ];
                $distribution[$score['nivel_riesgo']]++;
            }
        }

        // Ordenar por score descendente
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'success' => true,
            'total_clientes' => count($results),
            'distribucion' => $distribution,
            'score_promedio' => count($results) > 0
                ? round(array_sum(array_column($results, 'score')) / count($results), 1)
                : 0,
            'clientes' => $results,
            'generado_en' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Obtener clientes de alto riesgo
     *
     * @return array<string, mixed>
     */
    public function getHighRiskClients(int $limit = 20): array
    {
        $allScores = $this->calculateAllScores(500);

        if (!$allScores['success']) {
            return $allScores;
        }

        // Filtrar solo riesgosos y alto_riesgo
        $highRisk = array_filter(
            $allScores['clientes'],
            fn($c) => in_array($c['nivel'], ['riesgoso', 'alto_riesgo'])
        );

        // Ordenar por score ascendente (peores primero)
        usort($highRisk, fn($a, $b) => $a['score'] <=> $b['score']);

        return [
            'success' => true,
            'total_alto_riesgo' => count($highRisk),
            'clientes' => array_slice($highRisk, 0, $limit),
            'alerta' => count($highRisk) > 10
                ? 'Hay una cantidad significativa de clientes de alto riesgo'
                : null,
        ];
    }

    /**
     * Simular impacto de nuevo crédito
     *
     * @return array<string, mixed>
     */
    public function simulateCredit(int $clienteId, float $montoCredito, int $plazo): array
    {
        $scoreActual = $this->calculateScore($clienteId);

        if (!$scoreActual['success']) {
            return $scoreActual;
        }

        $deudaActual = $scoreActual['metricas']['deuda_actual'];
        $limiteRecomendado = $scoreActual['limite_credito_recomendado'];
        $nuevaDeuda = $deudaActual + $montoCredito;

        // Calcular utilización de crédito
        $utilizacion = $limiteRecomendado > 0
            ? ($nuevaDeuda / $limiteRecomendado) * 100
            : 100;

        // Estimar impacto en score
        $impactoScore = 0;
        if ($utilizacion > 100) {
            $impactoScore = -15; // Penalización fuerte
        } elseif ($utilizacion > 80) {
            $impactoScore = -10;
        } elseif ($utilizacion > 50) {
            $impactoScore = -5;
        }

        $scoreProyectado = max(0, $scoreActual['score'] + $impactoScore);
        $nivelProyectado = $this->determineRiskLevel($scoreProyectado);

        // Calcular cuota mensual estimada
        $cuotaMensual = $plazo > 0 ? $montoCredito / $plazo : $montoCredito;

        return [
            'success' => true,
            'cliente_id' => $clienteId,
            'simulacion' => [
                'monto_credito' => $montoCredito,
                'plazo_meses' => $plazo,
                'cuota_mensual_estimada' => round($cuotaMensual, 2),
            ],
            'situacion_actual' => [
                'score' => $scoreActual['score'],
                'nivel' => $scoreActual['nivel_riesgo'],
                'deuda_actual' => $deudaActual,
                'limite_credito' => $limiteRecomendado,
            ],
            'situacion_proyectada' => [
                'score' => $scoreProyectado,
                'nivel' => $nivelProyectado['nivel'],
                'deuda_total' => round($nuevaDeuda, 2),
                'utilizacion_credito' => round($utilizacion, 1),
                'impacto_score' => $impactoScore,
            ],
            'recomendacion' => $this->generateCreditRecommendation($utilizacion, $scoreActual['score'], $montoCredito),
        ];
    }

    // ========== MÉTODOS PRIVADOS ==========

    protected function getClienteData(int $clienteId): ?object
    {
        return DB::table('clientes')
            ->where('id', $clienteId)
            ->where('empresa_id', $this->empresaId)
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    protected function calculateMetrics(int $clienteId): array
    {
        // Fecha de primera compra (antigüedad)
        $primeraCompra = DB::table('ventas')
            ->where('cliente_id', $clienteId)
            ->where('empresa_id', $this->empresaId)
            ->min('fecha');

        $diasCliente = $primeraCompra
            ? Carbon::parse($primeraCompra)->diffInDays(Carbon::now())
            : 0;

        // Total de compras y monto
        $compras = DB::table('ventas')
            ->where('cliente_id', $clienteId)
            ->where('empresa_id', $this->empresaId)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(total), 0) as monto')
            ->first();

        // Frecuencia de compras (últimos 6 meses)
        $comprasRecientes = DB::table('ventas')
            ->where('cliente_id', $clienteId)
            ->where('empresa_id', $this->empresaId)
            ->where('fecha', '>=', Carbon::now()->subMonths(6))
            ->count();

        // Cuentas por cobrar
        $cuentas = DB::table('cuentas_por_cobrar')
            ->where('cliente_id', $clienteId)
            ->where('empresa_id', $this->empresaId)
            ->selectRaw('
                COALESCE(SUM(saldo), 0) as deuda_actual,
                COUNT(CASE WHEN fecha_vencimiento < NOW() AND saldo > 0 THEN 1 END) as facturas_vencidas,
                COALESCE(AVG(CASE WHEN fecha_vencimiento < NOW() THEN DATEDIFF(NOW(), fecha_vencimiento) END), 0) as dias_mora_promedio
            ')
            ->first();

        // Historial de pagos
        $pagos = DB::table('pagos_cuentas_cobrar as pcc')
            ->join('cuentas_por_cobrar as cc', 'pcc.cuenta_cobrar_id', '=', 'cc.id')
            ->where('cc.cliente_id', $clienteId)
            ->where('cc.empresa_id', $this->empresaId)
            ->selectRaw('
                COUNT(*) as total_pagos,
                COUNT(CASE WHEN pcc.fecha <= cc.fecha_vencimiento THEN 1 END) as pagos_puntuales
            ')
            ->first();

        $pctPagosPuntuales = $pagos->total_pagos > 0
            ? ($pagos->pagos_puntuales / $pagos->total_pagos) * 100
            : 100; // Si no hay pagos, asumimos bien

        return [
            'dias_cliente' => $diasCliente,
            'total_compras' => (int)$compras->total,
            'monto_total' => (float)$compras->monto,
            'promedio_compra' => $compras->total > 0 ? $compras->monto / $compras->total : 0,
            'compras_6_meses' => $comprasRecientes,
            'deuda_actual' => (float)($cuentas->deuda_actual ?? 0),
            'facturas_vencidas' => (int)($cuentas->facturas_vencidas ?? 0),
            'dias_mora_promedio' => (float)($cuentas->dias_mora_promedio ?? 0),
            'total_pagos' => (int)$pagos->total_pagos,
            'pagos_puntuales' => (int)$pagos->pagos_puntuales,
            'pct_pagos_puntuales' => $pctPagosPuntuales,
        ];
    }

    /**
     * @param array<string, mixed> $metricas
     */
    protected function scoreHistorialPagos(array $metricas): float
    {
        // Basado en % de pagos puntuales y días de mora
        $score = $metricas['pct_pagos_puntuales'];

        // Penalizar por mora
        if ($metricas['dias_mora_promedio'] > 30) {
            $score -= 30;
        } elseif ($metricas['dias_mora_promedio'] > 15) {
            $score -= 15;
        } elseif ($metricas['dias_mora_promedio'] > 7) {
            $score -= 5;
        }

        // Penalizar por facturas vencidas activas
        $score -= $metricas['facturas_vencidas'] * 5;

        return max(0, min(100, $score));
    }

    /**
     * @param array<string, mixed> $metricas
     */
    protected function scoreAntiguedad(array $metricas): float
    {
        $dias = $metricas['dias_cliente'];

        if ($dias >= 730) return 100;      // 2+ años
        if ($dias >= 365) return 80;       // 1+ año
        if ($dias >= 180) return 60;       // 6+ meses
        if ($dias >= 90) return 40;        // 3+ meses
        if ($dias >= 30) return 20;        // 1+ mes

        return 10; // Nuevo cliente
    }

    /**
     * @param array<string, mixed> $metricas
     */
    protected function scoreVolumenCompras(array $metricas): float
    {
        $monto = $metricas['monto_total'];

        // Escala logarítmica para monto total
        if ($monto >= 10000000) return 100;    // 10M+
        if ($monto >= 5000000) return 85;      // 5M+
        if ($monto >= 1000000) return 70;      // 1M+
        if ($monto >= 500000) return 55;       // 500K+
        if ($monto >= 100000) return 40;       // 100K+
        if ($monto >= 50000) return 25;        // 50K+

        return 10;
    }

    /**
     * @param array<string, mixed> $metricas
     */
    protected function scoreFrecuenciaCompras(array $metricas): float
    {
        $compras6Meses = $metricas['compras_6_meses'];

        if ($compras6Meses >= 24) return 100;   // 4+ por mes
        if ($compras6Meses >= 12) return 80;    // 2+ por mes
        if ($compras6Meses >= 6) return 60;     // 1 por mes
        if ($compras6Meses >= 3) return 40;     // Cada 2 meses
        if ($compras6Meses >= 1) return 20;

        return 5; // Sin compras recientes
    }

    /**
     * @param array<string, mixed> $metricas
     */
    protected function scoreMontoPromedio(array $metricas): float
    {
        $promedio = $metricas['promedio_compra'];

        if ($promedio >= 500000) return 100;
        if ($promedio >= 200000) return 80;
        if ($promedio >= 100000) return 60;
        if ($promedio >= 50000) return 40;
        if ($promedio >= 20000) return 20;

        return 10;
    }

    /**
     * @param array<string, mixed> $metricas
     */
    protected function scoreDeudaActual(array $metricas): float
    {
        $deuda = $metricas['deuda_actual'];
        $montoTotal = $metricas['monto_total'];

        // Sin deuda = excelente
        if ($deuda <= 0) return 100;

        // Ratio deuda/historial
        $ratio = $montoTotal > 0 ? ($deuda / $montoTotal) * 100 : 100;

        if ($ratio <= 5) return 90;
        if ($ratio <= 10) return 75;
        if ($ratio <= 20) return 60;
        if ($ratio <= 30) return 40;
        if ($ratio <= 50) return 20;

        return 5; // Deuda muy alta relativa al historial
    }

    /**
     * @return array<string, string>
     */
    protected function determineRiskLevel(float $score): array
    {
        foreach ($this->riskLevels as $nivel => $config) {
            if ($score >= $config['min'] && $score <= $config['max']) {
                return ['nivel' => $nivel, 'color' => $config['color']];
            }
        }
        return ['nivel' => 'alto_riesgo', 'color' => 'red'];
    }

    /**
     * @param array<string, mixed> $metricas
     */
    protected function calculateCreditLimit(float $score, array $metricas): float
    {
        // Base: promedio de compra * factor según score
        $base = $metricas['promedio_compra'];

        $factor = match (true) {
            $score >= 80 => 3.0,
            $score >= 60 => 2.0,
            $score >= 40 => 1.0,
            $score >= 20 => 0.5,
            default => 0,
        };

        $limite = $base * $factor;

        // Máximo basado en historial
        $maxHistorico = $metricas['monto_total'] * 0.1;

        return round(min($limite, max($maxHistorico, $limite * 0.5)), -3); // Redondear a miles
    }

    /**
     * @param array<string, mixed> $metricas
     */
    protected function calculateDefaultProbability(float $score, array $metricas): float
    {
        // Probabilidad inversa al score
        $probBase = 100 - $score;

        // Ajustar por facturas vencidas actuales
        $probBase += $metricas['facturas_vencidas'] * 5;

        // Ajustar por días de mora histórico
        if ($metricas['dias_mora_promedio'] > 30) {
            $probBase += 15;
        } elseif ($metricas['dias_mora_promedio'] > 15) {
            $probBase += 10;
        }

        return max(0, min(100, $probBase));
    }

    /**
     * @param array<string, mixed> $metricas
     * @return array<int, string>
     */
    protected function generateRecommendations(float $score, array $metricas): array
    {
        $recomendaciones = [];

        if ($score >= 80) {
            $recomendaciones[] = 'Cliente confiable. Puede ofrecer condiciones preferenciales.';
        } elseif ($score >= 60) {
            $recomendaciones[] = 'Buen cliente. Mantener condiciones actuales.';
        } elseif ($score >= 40) {
            $recomendaciones[] = 'Cliente regular. Monitorear pagos de cerca.';
        } else {
            $recomendaciones[] = 'Alto riesgo. Considerar venta solo de contado.';
        }

        if ($metricas['facturas_vencidas'] > 0) {
            $recomendaciones[] = "Tiene {$metricas['facturas_vencidas']} factura(s) vencida(s). Gestionar cobro.";
        }

        if ($metricas['dias_mora_promedio'] > 15) {
            $recomendaciones[] = 'Historial de pagos tardíos. Considerar reducir plazo de crédito.';
        }

        if ($metricas['compras_6_meses'] < 2) {
            $recomendaciones[] = 'Baja actividad reciente. Evaluar reactivación del cliente.';
        }

        return $recomendaciones;
    }

    protected function generateCreditRecommendation(float $utilizacion, float $score, float $monto): string
    {
        if ($utilizacion > 100) {
            return "❌ NO RECOMENDADO: El monto excede el límite de crédito disponible.";
        }

        if ($score < 40) {
            return "⚠️ PRECAUCIÓN: Cliente de alto riesgo. Considerar garantías adicionales.";
        }

        if ($utilizacion > 80) {
            return "⚠️ ACEPTABLE CON RESERVAS: Alta utilización de crédito. Monitorear de cerca.";
        }

        if ($utilizacion > 50) {
            return "✅ ACEPTABLE: Utilización moderada. Crédito viable.";
        }

        return "✅ RECOMENDADO: Buen margen de crédito disponible.";
    }
}

