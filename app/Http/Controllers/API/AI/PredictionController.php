<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\PredictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="AI - Predicciones",
 *     description="Endpoints para predicción de demanda e inventario usando IA"
 * )
 */
class PredictionController extends Controller
{
    public function __construct(
        private PredictionService $predictionService
    ) {}
    
    /**
     * @OA\Get(
     *     path="/api/ai/predictions/product/{productoId}",
     *     summary="Predicción de demanda para un producto",
     *     description="Genera predicción de demanda basada en historial de ventas",
     *     operationId="predictProductDemand",
     *     tags={"AI - Predicciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="productoId",
     *         in="path",
     *         required=true,
     *         description="ID del producto",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         required=false,
     *         description="Días a predecir (default: 30)",
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Predicción generada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="producto", type="object"),
     *             @OA\Property(property="stock_actual", type="number"),
     *             @OA\Property(property="estadisticas", type="object"),
     *             @OA\Property(property="prediccion", type="object"),
     *             @OA\Property(property="inventario", type="object"),
     *             @OA\Property(property="alerta", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Producto no encontrado"),
     *     @OA\Response(response=422, description="Datos insuficientes para predicción")
     * )
     */
    public function predictProduct(Request $request, int $productoId): JsonResponse
    {
        $user = $request->user();
        $days = (int)$request->input('days', 30);
        
        if ($days < 1 || $days > 365) {
            return response()->json([
                'success' => false,
                'error' => 'El rango de días debe estar entre 1 y 365',
            ], 422);
        }
        
        $prediction = $this->predictionService
            ->setEmpresa($user->empresa_id)
            ->predictProductDemand($productoId, $days);
        
        if (!$prediction['success']) {
            $statusCode = isset($prediction['error']) && str_contains($prediction['error'], 'no encontrado') 
                ? 404 
                : 422;
            return response()->json($prediction, $statusCode);
        }
        
        return response()->json($prediction);
    }
    
    /**
     * @OA\Get(
     *     path="/api/ai/predictions/alerts",
     *     summary="Alertas de stock bajo",
     *     description="Lista productos con stock bajo que requieren reabastecimiento",
     *     operationId="getLowStockAlerts",
     *     tags={"AI - Predicciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Número máximo de alertas (default: 20)",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Alertas obtenidas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="total_alertas", type="integer"),
     *             @OA\Property(property="resumen", type="object",
     *                 @OA\Property(property="critico", type="integer"),
     *                 @OA\Property(property="alto", type="integer"),
     *                 @OA\Property(property="medio", type="integer"),
     *                 @OA\Property(property="bajo", type="integer")
     *             ),
     *             @OA\Property(property="alertas", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function alerts(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = (int)$request->input('limit', 20);
        
        $limit = max(1, min(100, $limit));
        
        $alerts = $this->predictionService
            ->setEmpresa($user->empresa_id)
            ->getLowStockAlerts($limit);
        
        return response()->json($alerts);
    }
    
    /**
     * @OA\Get(
     *     path="/api/ai/predictions/recommendations",
     *     summary="Recomendaciones de compra",
     *     description="Genera recomendaciones de compra priorizadas usando IA",
     *     operationId="getPurchaseRecommendations",
     *     tags={"AI - Predicciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Recomendaciones generadas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="generado_por", type="string", example="ia"),
     *             @OA\Property(property="recomendaciones", type="object",
     *                 @OA\Property(property="prioridad_inmediata", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="proximos_7_dias", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="proximos_30_dias", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="recomendaciones_generales", type="array", @OA\Items(type="string"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function recommendations(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $recommendations = $this->predictionService
            ->setEmpresa($user->empresa_id)
            ->generatePurchaseRecommendations();
        
        return response()->json($recommendations);
    }
    
    /**
     * @OA\Get(
     *     path="/api/ai/predictions/trends",
     *     summary="Análisis de tendencias de ventas",
     *     description="Analiza tendencias de ventas por categoría",
     *     operationId="getSalesTrends",
     *     tags={"AI - Predicciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="months",
     *         in="query",
     *         required=false,
     *         description="Meses de historial a analizar (default: 6)",
     *         @OA\Schema(type="integer", default=6)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Tendencias analizadas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="periodo", type="object"),
     *             @OA\Property(property="categorias", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="top_crecimiento", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="top_decrecimiento", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function trends(Request $request): JsonResponse
    {
        $user = $request->user();
        $months = (int)$request->input('months', 6);
        
        $months = max(1, min(24, $months));
        
        $trends = $this->predictionService
            ->setEmpresa($user->empresa_id)
            ->analyzeSalesTrends($months);
        
        return response()->json($trends);
    }
    
    /**
     * @OA\Get(
     *     path="/api/ai/predictions/revenue",
     *     summary="Predicción de ingresos",
     *     description="Predice ingresos para el próximo período",
     *     operationId="predictRevenue",
     *     tags={"AI - Predicciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         required=false,
     *         description="Días a predecir (default: 30)",
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Predicción generada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="periodo_historico", type="object"),
     *             @OA\Property(property="estadisticas", type="object"),
     *             @OA\Property(property="prediccion", type="object",
     *                 @OA\Property(property="dias", type="integer"),
     *                 @OA\Property(property="ingreso_estimado", type="number"),
     *                 @OA\Property(property="rango_minimo", type="number"),
     *                 @OA\Property(property="rango_maximo", type="number"),
     *                 @OA\Property(property="confianza", type="string")
     *             ),
     *             @OA\Property(property="analisis_semanal", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="moneda", type="string", example="CRC")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Historial insuficiente")
     * )
     */
    public function revenue(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = (int)$request->input('days', 30);
        
        $days = max(1, min(365, $days));
        
        $prediction = $this->predictionService
            ->setEmpresa($user->empresa_id)
            ->predictRevenue($days);
        
        if (!$prediction['success']) {
            return response()->json($prediction, 422);
        }
        
        return response()->json($prediction);
    }
    
    /**
     * @OA\Get(
     *     path="/api/ai/predictions/dashboard",
     *     summary="Dashboard de predicciones",
     *     description="Resumen ejecutivo con todas las métricas de predicción",
     *     operationId="getPredictionsDashboard",
     *     tags={"AI - Predicciones"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard generado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="alertas_inventario", type="object"),
     *             @OA\Property(property="prediccion_ingresos", type="object"),
     *             @OA\Property(property="tendencias_categorias", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado")
     * )
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $service = $this->predictionService->setEmpresa($user->empresa_id);
        
        // Obtener todas las métricas en paralelo conceptualmente
        $alerts = $service->getLowStockAlerts(10);
        $revenue = $service->predictRevenue(30);
        $trends = $service->analyzeSalesTrends(3);
        
        return response()->json([
            'success' => true,
            'alertas_inventario' => [
                'total' => $alerts['total_alertas'] ?? 0,
                'resumen' => $alerts['resumen'] ?? [],
                'productos_criticos' => array_slice(
                    array_filter($alerts['alertas'] ?? [], fn($a) => $a['nivel_alerta'] === 'critico'),
                    0,
                    5
                ),
            ],
            'prediccion_ingresos' => $revenue['success'] ? [
                'estimado_30_dias' => $revenue['prediccion']['ingreso_estimado'] ?? 0,
                'tendencia' => $revenue['estadisticas']['direccion'] ?? 'desconocida',
                'mejor_dia' => collect($revenue['analisis_semanal'] ?? [])
                    ->firstWhere('mejor_dia', true)['dia'] ?? null,
            ] : [
                'mensaje' => $revenue['error'] ?? 'No disponible',
            ],
            'tendencias_categorias' => [
                'periodo_meses' => 3,
                'top_crecimiento' => $trends['top_crecimiento'] ?? [],
                'top_decrecimiento' => $trends['top_decrecimiento'] ?? [],
            ],
            'generado_en' => now()->toIso8601String(),
        ]);
    }
}

