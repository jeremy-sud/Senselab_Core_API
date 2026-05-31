<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\Producto;
use App\Models\InventarioProducto;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio de Predicción de Demanda e Inventario
 *
 * Utiliza análisis estadístico e IA para predecir demanda de productos
 * y generar alertas de reabastecimiento.
 */
class PredictionService
{
    private OpenAIService $openAI;
    private int $empresaId;
    
    /**
     * Configuración del servicio
     * @var array<string, mixed>
     */
    private array $config = [
        'forecast_days' => 30,           // Días a predecir
        'history_months' => 6,           // Meses de historial a analizar
        'min_sales_for_prediction' => 5, // Mínimo de ventas para predecir
        'safety_stock_days' => 7,        // Días de stock de seguridad
        'cache_ttl' => 3600,             // Cache por 1 hora
    ];
    
    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }
    
    /**
     * Establecer la empresa para las predicciones
     */
    public function setEmpresa(int $empresaId): self
    {
        $this->empresaId = $empresaId;
        return $this;
    }
    
    /**
     * Obtener predicción de demanda para un producto específico
     *
     * @return array<string, mixed>
     */
    public function predictProductDemand(int $productoId, int $days = 30): array
    {
        $cacheKey = "prediction:demand:{$this->empresaId}:{$productoId}:{$days}";
        
        /** @var array<string, mixed> $result */
        // @phpstan-ignore-next-line
        $result = Cache::remember($cacheKey, $this->config['cache_ttl'], function () use ($productoId, $days): array {
            $producto = Producto::where('id', $productoId)
                ->where('empresa_id', $this->empresaId)
                ->first();
            
            if (!$producto) {
                return [
                    'success' => false,
                    'error' => 'Producto no encontrado',
                ];
            }
            
            // Obtener historial de ventas
            $salesHistory = $this->getSalesHistory($productoId);
            
            if ($salesHistory->count() < $this->config['min_sales_for_prediction']) {
                return [
                    'success' => false,
                    'error' => 'Historial de ventas insuficiente para predicción',
                    'min_required' => $this->config['min_sales_for_prediction'],
                    'current_sales' => $salesHistory->count(),
                ];
            }
            
            // Calcular estadísticas
            $stats = $this->calculateSalesStatistics($salesHistory);
            
            // Predicción usando regresión lineal simple + ajuste por tendencia
            $prediction = $this->calculatePrediction($salesHistory, $stats, $days);
            
            // Obtener stock actual
            $currentStock = $this->getCurrentStock($productoId);
            
            // Calcular días de stock restantes
            $daysOfStock = $stats['daily_average'] > 0
                ? round($currentStock / $stats['daily_average'], 1)
                : 999;
            
            // Calcular punto de reorden
            $reorderPoint = $this->calculateReorderPoint($stats);
            
            // Determinar urgencia
            $urgency = $this->determineUrgency($currentStock, $reorderPoint, $daysOfStock);
            
            return [
                'success' => true,
                'producto' => [
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'unidad_medida' => $producto->unidad_medida,
                ],
                'stock_actual' => $currentStock,
                'estadisticas' => [
                    'promedio_diario' => round($stats['daily_average'], 2),
                    'promedio_semanal' => round($stats['weekly_average'], 2),
                    'promedio_mensual' => round($stats['monthly_average'], 2),
                    'desviacion_estandar' => round($stats['std_deviation'], 2),
                    'tendencia' => $stats['trend'],
                    'coeficiente_variacion' => round($stats['coefficient_variation'], 2),
                ],
                'prediccion' => [
                    'dias' => $days,
                    'demanda_estimada' => round($prediction['estimated_demand'], 0),
                    'rango_minimo' => round($prediction['min_demand'], 0),
                    'rango_maximo' => round($prediction['max_demand'], 0),
                    'confianza' => $prediction['confidence'],
                ],
                'inventario' => [
                    'dias_stock_restante' => $daysOfStock,
                    'punto_reorden' => round($reorderPoint, 0),
                    'cantidad_sugerida_compra' => $this->calculateSuggestedOrder($stats, $currentStock, $reorderPoint),
                    'fecha_estimada_agotamiento' => $daysOfStock < 999
                        ? Carbon::now()->addDays((int)$daysOfStock)->format('Y-m-d')
                        : null,
                ],
                'alerta' => [
                    'nivel' => $urgency['level'],
                    'mensaje' => $urgency['message'],
                    'accion_recomendada' => $urgency['action'],
                ],
            ];
        });

        return $result;
    }
    
    /**
     * Obtener productos con bajo stock que necesitan reabastecimiento
     *
     * @return array<string, mixed>
     */
    public function getLowStockAlerts(int $limit = 20): array
    {
        $cacheKey = "prediction:alerts:{$this->empresaId}";
        
        /** @var array<string, mixed> $result */
        $result = Cache::remember($cacheKey, $this->config['cache_ttl'], function () use ($limit): array {
            // Obtener productos con su stock actual y ventas promedio
            $productos = DB::table('productos as p')
                ->leftJoin('inventarios as i', function ($join) {
                    $join->on('p.id', '=', 'i.producto_id')
                        ->where('i.empresa_id', '=', $this->empresaId);
                })
                ->leftJoin('detalle_ventas as dv', 'p.id', '=', 'dv.producto_id')
                ->leftJoin('ventas as v', function ($join) {
                    $join->on('dv.venta_id', '=', 'v.id')
                        ->where('v.empresa_id', '=', $this->empresaId)
                        ->where('v.fecha_venta', '>=', Carbon::now()->subMonths(3));
                })
                ->where('p.empresa_id', $this->empresaId)
                ->where('p.activo', true)
                ->groupBy('p.id', 'p.codigo', 'p.nombre', 'p.stock_minimo', 'i.cantidad')
                ->select([
                    'p.id',
                    'p.codigo',
                    'p.nombre',
                    'p.stock_minimo',
                    DB::raw('COALESCE(i.cantidad, 0) as stock_actual'),
                    DB::raw('COALESCE(SUM(dv.cantidad), 0) as total_vendido'),
                    DB::raw('COALESCE(SUM(dv.cantidad) / 90, 0) as promedio_diario'),
                ])
                ->havingRaw('COALESCE(i.cantidad, 0) <= COALESCE(p.stock_minimo, 0) * 1.5 OR COALESCE(i.cantidad, 0) / NULLIF(COALESCE(SUM(dv.cantidad) / 90, 1), 0) <= ?', [$this->config['safety_stock_days']])
                ->orderByRaw('COALESCE(i.cantidad, 0) / NULLIF(COALESCE(SUM(dv.cantidad) / 90, 1), 0) ASC')
                ->limit($limit)
                ->get();
            
            $alerts = [];
            
            foreach ($productos as $producto) {
                $promedioDiario = (float)$producto->promedio_diario;
                $stockActual = (float)$producto->stock_actual;
                $stockMinimo = (float)($producto->stock_minimo ?? 0);
                
                $diasStock = $promedioDiario > 0
                    ? round($stockActual / $promedioDiario, 1)
                    : ($stockActual > 0 ? 999 : 0);
                
                $nivel = 'normal';
                $mensaje = '';
                
                if ($diasStock <= 0 || $stockActual <= 0) {
                    $nivel = 'critico';
                    $mensaje = 'Sin stock disponible';
                } elseif ($diasStock <= 3) {
                    $nivel = 'critico';
                    $mensaje = "Stock para {$diasStock} días";
                } elseif ($diasStock <= 7) {
                    $nivel = 'alto';
                    $mensaje = "Stock para {$diasStock} días";
                } elseif ($stockActual <= $stockMinimo) {
                    $nivel = 'medio';
                    $mensaje = 'Stock por debajo del mínimo';
                } else {
                    $nivel = 'bajo';
                    $mensaje = 'Stock bajo pero suficiente';
                }
                
                $alerts[] = [
                    'producto_id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'stock_actual' => (int)$stockActual,
                    'stock_minimo' => (int)$stockMinimo,
                    'promedio_diario' => round($promedioDiario, 2),
                    'dias_stock' => $diasStock,
                    'nivel_alerta' => $nivel,
                    'mensaje' => $mensaje,
                    'cantidad_sugerida' => (int)max(0, ($promedioDiario * 30) - $stockActual + ($stockMinimo * 0.5)),
                ];
            }
            
            // Ordenar por urgencia
            $order = ['critico' => 1, 'alto' => 2, 'medio' => 3, 'bajo' => 4];
            usort($alerts, fn($a, $b) => $order[$a['nivel_alerta']] <=> $order[$b['nivel_alerta']]);
            
            return [
                'success' => true,
                'total_alertas' => count($alerts),
                'resumen' => [
                    'critico' => count(array_filter($alerts, fn($a) => $a['nivel_alerta'] === 'critico')),
                    'alto' => count(array_filter($alerts, fn($a) => $a['nivel_alerta'] === 'alto')),
                    'medio' => count(array_filter($alerts, fn($a) => $a['nivel_alerta'] === 'medio')),
                    'bajo' => count(array_filter($alerts, fn($a) => $a['nivel_alerta'] === 'bajo')),
                ],
                'alertas' => $alerts,
                'generado_en' => Carbon::now()->toIso8601String(),
            ];
        });
        
        return $result;
    }
    
    /**
     * Generar recomendaciones de compra usando IA
     *
     * @return array<string, mixed>
     */
    public function generatePurchaseRecommendations(): array
    {
        try {
            // Obtener alertas de stock bajo
            $alerts = $this->getLowStockAlerts(50);
            
            if (!$alerts['success'] || empty($alerts['alertas'])) {
                return [
                    'success' => true,
                    'mensaje' => 'No hay productos que requieran reabastecimiento urgente',
                    'recomendaciones' => [],
                ];
            }
            
            // Preparar contexto para IA
            $context = $this->buildPurchaseContext($alerts['alertas']);
            
            // Solicitar análisis a OpenAI
            $prompt = <<<PROMPT
Eres un experto en gestión de inventarios y compras para empresas en Costa Rica.
Analiza los siguientes productos con bajo stock y genera recomendaciones de compra priorizadas.

Considera:
1. Urgencia basada en días de stock restante
2. Volumen de ventas histórico
3. Optimización de costos (consolidar pedidos a mismo proveedor si es posible)
4. Temporada actual (estamos en {$context['mes_actual']})

Productos con bajo stock:
{$context['productos_texto']}

Genera un plan de compras priorizado en formato JSON con la siguiente estructura:
{
    "prioridad_inmediata": [productos para comprar hoy],
    "proximos_7_dias": [productos para comprar esta semana],
    "proximos_30_dias": [productos para planificar],
    "recomendaciones_generales": ["recomendación 1", "recomendación 2"]
}

Cada producto debe incluir: codigo, nombre, cantidad_sugerida, razon.
PROMPT;
            
            $response = $this->openAI->chat($prompt, [
                'role' => 'inventory_analyst',
                'response_format' => 'json',
            ]);
            
            // Parsear respuesta
            $recommendations = json_decode($response['content'], true);
            
            if (!$recommendations) {
                // Si no se puede parsear, usar las alertas directamente
                return $this->fallbackRecommendations($alerts['alertas']);
            }
            
            return [
                'success' => true,
                'generado_por' => 'ia',
                'fecha' => Carbon::now()->toIso8601String(),
                'recomendaciones' => $recommendations,
                'resumen_alertas' => $alerts['resumen'],
            ];
            
        } catch (\Exception $e) {
            Log::warning('Error generando recomendaciones con IA, usando fallback', [
                'error' => $e->getMessage(),
            ]);
            
            $alerts = $this->getLowStockAlerts(50);
            return $this->fallbackRecommendations($alerts['alertas'] ?? []);
        }
    }
    
    /**
     * Análisis de tendencias de ventas por categoría
     *
     * @return array<string, mixed>
     */
    public function analyzeSalesTrends(int $months = 6): array
    {
        $cacheKey = "prediction:trends:{$this->empresaId}:{$months}";
        
        /** @var array<string, mixed> $result */
        $result = Cache::remember($cacheKey, $this->config['cache_ttl'] * 2, function () use ($months): array {
            $startDate = Carbon::now()->subMonths($months);
            
            // Ventas por mes y categoría
            $salesByMonth = DB::table('ventas as v')
                ->join('detalle_ventas as dv', 'v.id', '=', 'dv.venta_id')
                ->join('productos as p', 'dv.producto_id', '=', 'p.id')
                ->leftJoin('categorias as c', 'p.categoria_id', '=', 'c.id')
                ->where('v.empresa_id', $this->empresaId)
                ->where('v.fecha_venta', '>=', $startDate)
                ->groupBy(DB::raw('YEAR(v.fecha_venta)'), DB::raw('MONTH(v.fecha_venta)'), 'c.id', 'c.nombre')
                ->select([
                    DB::raw('YEAR(v.fecha_venta) as ano'),
                    DB::raw('MONTH(v.fecha_venta) as mes'),
                    'c.id as categoria_id',
                    DB::raw('COALESCE(c.nombre, "Sin categoría") as categoria'),
                    DB::raw('SUM(dv.cantidad) as unidades'),
                    DB::raw('SUM(dv.subtotal) as total'),
                ])
                ->get();
            
            // Agrupar por categoría
            $trends = [];
            foreach ($salesByMonth as $row) {
                $catId = $row->categoria_id ?? 0;
                if (!isset($trends[$catId])) {
                    $trends[$catId] = [
                        'categoria' => $row->categoria,
                        'meses' => [],
                        'total_unidades' => 0,
                        'total_ventas' => 0,
                    ];
                }
                
                $mesKey = "{$row->ano}-" . str_pad($row->mes, 2, '0', STR_PAD_LEFT);
                $trends[$catId]['meses'][$mesKey] = [
                    'unidades' => (int)$row->unidades,
                    'total' => (float)$row->total,
                ];
                $trends[$catId]['total_unidades'] += (int)$row->unidades;
                $trends[$catId]['total_ventas'] += (float)$row->total;
            }
            
            // Calcular tendencia para cada categoría
            foreach ($trends as &$trend) {
                $values = array_column($trend['meses'], 'total');
                if (count($values) >= 2) {
                    $firstHalf = array_sum(array_slice($values, 0, (int)(count($values) / 2)));
                    $secondHalf = array_sum(array_slice($values, (int)(count($values) / 2)));
                    
                    if ($firstHalf > 0) {
                        $change = (($secondHalf - $firstHalf) / $firstHalf) * 100;
                        $trend['tendencia'] = round($change, 1);
                        $trend['direccion'] = $change > 5 ? 'crecimiento' : ($change < -5 ? 'decrecimiento' : 'estable');
                    } else {
                        $trend['tendencia'] = 0;
                        $trend['direccion'] = 'estable';
                    }
                } else {
                    $trend['tendencia'] = 0;
                    $trend['direccion'] = 'datos_insuficientes';
                }
            }
            
            // Ordenar por ventas totales
            uasort($trends, fn($a, $b) => $b['total_ventas'] <=> $a['total_ventas']);
            
            return [
                'success' => true,
                'periodo' => [
                    'inicio' => $startDate->format('Y-m-d'),
                    'fin' => Carbon::now()->format('Y-m-d'),
                    'meses' => $months,
                ],
                'categorias' => array_values($trends),
                'top_crecimiento' => $this->getTopByTrend($trends, 'crecimiento'),
                'top_decrecimiento' => $this->getTopByTrend($trends, 'decrecimiento'),
            ];
        });
        
        return $result;
    }
    
    /**
     * Predicción de ingresos para el próximo período
     *
     * @return array<string, mixed>
     */
    public function predictRevenue(int $days = 30): array
    {
        $cacheKey = "prediction:revenue:{$this->empresaId}:{$days}";
        
        /** @var array<string, mixed> $result */
        $result = Cache::remember($cacheKey, $this->config['cache_ttl'], function () use ($days): array {
            $historyMonths = 6;
            $startDate = Carbon::now()->subMonths($historyMonths);
            
            // Obtener ventas diarias históricas
            $dailySales = DB::table('ventas')
                ->where('empresa_id', $this->empresaId)
                ->where('fecha_venta', '>=', $startDate)
                ->groupBy('fecha_venta')
                ->select([
                    'fecha_venta',
                    DB::raw('SUM(monto_total_venta) as total'),
                    DB::raw('COUNT(*) as num_ventas'),
                ])
                ->orderBy('fecha_venta')
                ->get();
            
            if ($dailySales->count() < 30) {
                return [
                    'success' => false,
                    'error' => 'Historial insuficiente para predicción de ingresos',
                    'dias_requeridos' => 30,
                    'dias_disponibles' => $dailySales->count(),
                ];
            }
            
            // Calcular estadísticas
            $totals = $dailySales->pluck('total')->toArray();
            $average = array_sum($totals) / count($totals);
            $stdDev = $this->standardDeviation($totals);
            
            // Calcular tendencia
            $trend = $this->calculateLinearTrend($totals);
            
            // Proyectar ingresos
            $lastTotal = end($totals);
            $projectedDaily = $lastTotal + ($trend['slope'] * ($days / 2));
            $projectedTotal = $projectedDaily * $days;
            
            // Calcular rangos con intervalo de confianza
            $confidence = 0.95;
            $zScore = 1.96; // Para 95% de confianza
            $margin = $zScore * ($stdDev / sqrt(count($totals))) * $days;
            
            // Análisis por día de semana
            $byDayOfWeek = [];
            foreach ($dailySales as $sale) {
                $dayOfWeek = Carbon::parse($sale->fecha_venta)->dayOfWeek;
                if (!isset($byDayOfWeek[$dayOfWeek])) {
                    $byDayOfWeek[$dayOfWeek] = [];
                }
                $byDayOfWeek[$dayOfWeek][] = (float)$sale->total;
            }
            
            $weekdayAnalysis = [];
            $dayNames = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            foreach ($byDayOfWeek as $day => $sales) {
                $weekdayAnalysis[] = [
                    'dia' => $dayNames[$day],
                    'promedio' => round(array_sum($sales) / count($sales), 2),
                    'mejor_dia' => false,
                ];
            }
            
            // Marcar mejor día
            usort($weekdayAnalysis, fn($a, $b) => $b['promedio'] <=> $a['promedio']);
            if (!empty($weekdayAnalysis)) {
                $weekdayAnalysis[0]['mejor_dia'] = true;
            }
            
            return [
                'success' => true,
                'periodo_historico' => [
                    'dias' => $dailySales->count(),
                    'inicio' => $startDate->format('Y-m-d'),
                    'fin' => Carbon::now()->format('Y-m-d'),
                ],
                'estadisticas' => [
                    'promedio_diario' => round($average, 2),
                    'desviacion_estandar' => round($stdDev, 2),
                    'tendencia_diaria' => round($trend['slope'], 2),
                    'direccion' => $trend['slope'] > 0 ? 'crecimiento' : ($trend['slope'] < 0 ? 'decrecimiento' : 'estable'),
                ],
                'prediccion' => [
                    'dias' => $days,
                    'ingreso_estimado' => round($projectedTotal, 2),
                    'rango_minimo' => round(max(0, $projectedTotal - $margin), 2),
                    'rango_maximo' => round($projectedTotal + $margin, 2),
                    'confianza' => ($confidence * 100) . '%',
                    'promedio_diario_proyectado' => round($projectedDaily, 2),
                ],
                'analisis_semanal' => $weekdayAnalysis,
                'moneda' => 'CRC',
            ];
        });
        
        return $result;
    }
    
    // ========== MÉTODOS PRIVADOS ==========
    
    /**
     * Obtener historial de ventas de un producto
     */
    private function getSalesHistory(int $productoId): Collection
    {
        $startDate = Carbon::now()->subMonths($this->config['history_months']);
        
        return DB::table('detalle_ventas as dv')
            ->join('ventas as v', 'dv.venta_id', '=', 'v.id')
            ->where('dv.producto_id', $productoId)
            ->where('v.empresa_id', $this->empresaId)
            ->where('v.fecha_venta', '>=', $startDate)
            ->select([
                'v.fecha_venta',
                'dv.cantidad',
                'dv.precio_unitario',
                'dv.subtotal',
            ])
            ->orderBy('v.fecha_venta')
            ->get();
    }
    
    /**
     * Calcular estadísticas de ventas
     *
     * @param Collection<int, mixed> $salesHistory
     * @return array<string, mixed>
     */
    private function calculateSalesStatistics(Collection $salesHistory): array
    {
        $quantities = $salesHistory->pluck('cantidad')->toArray();
        $totalQuantity = array_sum($quantities);
        $days = max(1, Carbon::parse($salesHistory->first()->fecha_venta)
            ->diffInDays(Carbon::parse($salesHistory->last()->fecha_venta)));
        
        $dailyAverage = $totalQuantity / max(1, $days);
        $weeklyAverage = $dailyAverage * 7;
        $monthlyAverage = $dailyAverage * 30;
        
        $stdDev = $this->standardDeviation($quantities);
        $coefficientVariation = $dailyAverage > 0 ? ($stdDev / $dailyAverage) * 100 : 0;
        
        // Calcular tendencia
        $trend = $this->calculateLinearTrend($quantities);
        
        return [
            'total_quantity' => $totalQuantity,
            'total_sales' => $salesHistory->count(),
            'days_analyzed' => $days,
            'daily_average' => $dailyAverage,
            'weekly_average' => $weeklyAverage,
            'monthly_average' => $monthlyAverage,
            'std_deviation' => $stdDev,
            'coefficient_variation' => $coefficientVariation,
            'trend' => $trend['slope'] > 0.1 ? 'creciente' : ($trend['slope'] < -0.1 ? 'decreciente' : 'estable'),
            'trend_slope' => $trend['slope'],
        ];
    }
    
    /**
     * Calcular predicción de demanda
     *
     * @param Collection<int, mixed> $salesHistory
     * @param array<string, mixed> $stats
     * @return array<string, mixed>
     */
    private function calculatePrediction(Collection $salesHistory, array $stats, int $days): array
    {
        // Predicción base usando promedio + tendencia
        $basePrediction = $stats['daily_average'] * $days;
        $trendAdjustment = $stats['trend_slope'] * $days;
        $estimatedDemand = $basePrediction + $trendAdjustment;
        
        // Calcular rango con desviación estándar
        $margin = $stats['std_deviation'] * sqrt($days) * 1.96; // 95% confianza
        
        // Determinar nivel de confianza
        $confidence = 'alta';
        if ($stats['coefficient_variation'] > 50) {
            $confidence = 'baja';
        } elseif ($stats['coefficient_variation'] > 25) {
            $confidence = 'media';
        }
        
        return [
            'estimated_demand' => max(0, $estimatedDemand),
            'min_demand' => max(0, $estimatedDemand - $margin),
            'max_demand' => $estimatedDemand + $margin,
            'confidence' => $confidence,
        ];
    }
    
    /**
     * Obtener stock actual de un producto
     */
    private function getCurrentStock(int $productoId): float
    {
        $inventario = InventarioProducto::whereHas('almacen', function ($query) {
            $query->where('empresa_id', $this->empresaId);
        })
            ->where('producto_id', $productoId)
            ->first();
        
        return $inventario ? (float)$inventario->stock_actual : 0;
    }
    
    /**
     * Calcular punto de reorden
     *
     * @param array<string, mixed> $stats
     */
    private function calculateReorderPoint(array $stats): float
    {
        // Punto de reorden = Demanda durante lead time + Stock de seguridad
        $leadTimeDays = 7; // Asumimos 7 días de tiempo de entrega
        $demandDuringLeadTime = $stats['daily_average'] * $leadTimeDays;
        $safetyStock = $stats['std_deviation'] * sqrt($leadTimeDays) * 1.65; // 95% nivel de servicio
        
        return $demandDuringLeadTime + $safetyStock;
    }
    
    /**
     * Calcular cantidad sugerida de compra
     *
     * @param array<string, mixed> $stats
     */
    private function calculateSuggestedOrder(array $stats, float $currentStock, float $reorderPoint): int
    {
        if ($currentStock >= $reorderPoint) {
            return 0;
        }
        
        // Cantidad económica de pedido simplificada (EOQ)
        // EOQ = sqrt((2 * Demanda anual * Costo de ordenar) / Costo de mantener)
        // Simplificamos asumiendo 1 mes de stock
        $monthlyDemand = $stats['monthly_average'];
        $suggestedQuantity = ($monthlyDemand * 1.5) - $currentStock;
        
        return (int)max(0, ceil($suggestedQuantity));
    }
    
    /**
     * Determinar nivel de urgencia
     *
     * @return array<string, string>
     */
    private function determineUrgency(float $currentStock, float $reorderPoint, float $daysOfStock): array
    {
        if ($currentStock <= 0) {
            return [
                'level' => 'critico',
                'message' => 'Sin stock. Se requiere reabastecimiento inmediato.',
                'action' => 'Realizar orden de compra urgente',
            ];
        }
        
        if ($daysOfStock <= 3) {
            return [
                'level' => 'critico',
                'message' => "Stock crítico. Solo quedan {$daysOfStock} días de inventario.",
                'action' => 'Realizar orden de compra inmediata',
            ];
        }
        
        if ($daysOfStock <= 7 || $currentStock <= $reorderPoint) {
            return [
                'level' => 'alto',
                'message' => 'Stock bajo. Se recomienda reabastecer pronto.',
                'action' => 'Programar orden de compra esta semana',
            ];
        }
        
        if ($daysOfStock <= 14) {
            return [
                'level' => 'medio',
                'message' => 'Stock moderado. Planificar reabastecimiento.',
                'action' => 'Incluir en próxima orden de compra',
            ];
        }
        
        return [
            'level' => 'bajo',
            'message' => 'Stock suficiente.',
            'action' => 'Monitorear normalmente',
        ];
    }
    
    /**
     * Calcular desviación estándar
     *
     * @param array<int, float|int> $values
     */
    private function standardDeviation(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / $count;
        $sumSquaredDiff = 0;
        
        foreach ($values as $value) {
            $sumSquaredDiff += pow($value - $mean, 2);
        }
        
        return sqrt($sumSquaredDiff / ($count - 1));
    }
    
    /**
     * Calcular tendencia lineal
     *
     * @param array<int, float|int> $values
     * @return array<string, float>
     */
    private function calculateLinearTrend(array $values): array
    {
        $n = count($values);
        if ($n < 2) {
            return ['slope' => 0, 'intercept' => $values[0] ?? 0];
        }
        
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $values[$i];
            $sumXY += $i * $values[$i];
            $sumX2 += $i * $i;
        }
        
        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        
        if ($denominator == 0) {
            return ['slope' => 0, 'intercept' => $sumY / $n];
        }
        
        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;
        
        return [
            'slope' => $slope,
            'intercept' => $intercept,
        ];
    }
    
    /**
     * Construir contexto para recomendaciones de compra
     *
     * @param array<string, mixed> $alerts
     * @return array<string, mixed>
     */
    private function buildPurchaseContext(array $alerts): array
    {
        $productosTexto = '';
        foreach ($alerts as $alert) {
            $productosTexto .= "- {$alert['codigo']}: {$alert['nombre']} | Stock: {$alert['stock_actual']} | ";
            $productosTexto .= "Promedio diario: {$alert['promedio_diario']} | Días stock: {$alert['dias_stock']} | ";
            $productosTexto .= "Nivel: {$alert['nivel_alerta']}\n";
        }
        
        $date = Carbon::now();
        $date->setLocale('es');
        
        return [
            'mes_actual' => $date->monthName,
            'productos_texto' => $productosTexto,
            'total_productos' => count($alerts),
        ];
    }
    
    /**
     * Recomendaciones fallback sin IA
     *
     * @param array<string, mixed> $alerts
     * @return array<string, mixed>
     */
    private function fallbackRecommendations(array $alerts): array
    {
        $prioridadInmediata = [];
        $proximos7Dias = [];
        $proximos30Dias = [];
        
        foreach ($alerts as $alert) {
            $item = [
                'codigo' => $alert['codigo'],
                'nombre' => $alert['nombre'],
                'cantidad_sugerida' => $alert['cantidad_sugerida'],
                'razon' => $alert['mensaje'],
            ];
            
            switch ($alert['nivel_alerta']) {
                case 'critico':
                    $prioridadInmediata[] = $item;
                    break;
                case 'alto':
                    $proximos7Dias[] = $item;
                    break;
                default:
                    $proximos30Dias[] = $item;
            }
        }
        
        return [
            'success' => true,
            'generado_por' => 'reglas',
            'fecha' => Carbon::now()->toIso8601String(),
            'recomendaciones' => [
                'prioridad_inmediata' => $prioridadInmediata,
                'proximos_7_dias' => $proximos7Dias,
                'proximos_30_dias' => $proximos30Dias,
                'recomendaciones_generales' => [
                    'Revisar proveedores para productos críticos',
                    'Considerar establecer stock mínimo para productos sin configurar',
                    'Analizar patrones de venta estacionales',
                ],
            ],
        ];
    }
    
    /**
     * Obtener top categorías por tendencia
     *
     * @param array<int, array<string, mixed>> $trends
     * @return array<int, array<string, mixed>>
     */
    private function getTopByTrend(array $trends, string $direction, int $limit = 3): array
    {
        $filtered = array_filter($trends, fn($t) => $t['direccion'] === $direction);
        
        if ($direction === 'crecimiento') {
            uasort($filtered, fn($a, $b) => $b['tendencia'] <=> $a['tendencia']);
        } else {
            uasort($filtered, fn($a, $b) => $a['tendencia'] <=> $b['tendencia']);
        }
        
        return array_slice(array_values($filtered), 0, $limit);
    }
}

