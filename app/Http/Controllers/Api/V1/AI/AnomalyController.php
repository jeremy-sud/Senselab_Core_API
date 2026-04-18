<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\AnomalyDetectionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * Controller para detección de anomalías financieras mediante IA
 *
 * Proporciona endpoints para detectar transacciones sospechosas,
 * anomalías en ventas, flujo de caja y errores contables.
 *
 * @OA\Tag(
 *     name="AI - Detección de Anomalías",
 *     description="Endpoints para detección de anomalías financieras mediante IA"
 * )
 */
class AnomalyController extends Controller
{
    public function __construct(
        private AnomalyDetectionService $anomalyService
    ) {}

    /**
     * @OA\Post(
     *     path="/api/v1/ai/anomalies/sales",
     *     summary="Detectar anomalías en ventas",
     *     description="Analiza transacciones de ventas para detectar patrones anómalos usando IA",
     *     operationId="detectSalesAnomalies",
     *     tags={"AI - Detección de Anomalías"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"start_date", "end_date"},
     *             @OA\Property(property="start_date", type="string", format="date", example="2026-01-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2026-03-31"),
     *             @OA\Property(property="sensitivity", type="string", enum={"low", "medium", "high"}, example="medium")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Anomalías detectadas exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="period", type="object",
     *                     @OA\Property(property="start", type="string", format="date"),
     *                     @OA\Property(property="end", type="string", format="date")
     *                 ),
     *                 @OA\Property(property="sensitivity", type="string"),
     *                 @OA\Property(property="analyzed_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function detectSalesAnomalies(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'sensitivity' => 'nullable|in:low,medium,high',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $empresaId = $request->user()->empresa_id;
            $startDate = Carbon::parse($request->input('start_date'));

            $this->anomalyService->setEmpresa($empresaId);
            $result = $this->anomalyService->detectSalesAnomalies($startDate);

            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'period' => [
                        'start' => $request->input('start_date'),
                        'end' => $request->input('end_date'),
                    ],
                    'sensitivity' => $request->input('sensitivity', 'medium'),
                    'analyzed_at' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al detectar anomalías en ventas',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ai/anomalies/cash-flow",
     *     summary="Detectar anomalías en flujo de caja",
     *     description="Analiza el flujo de caja para identificar movimientos inusuales",
     *     operationId="detectCashFlowAnomalies",
     *     tags={"AI - Detección de Anomalías"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"start_date", "end_date"},
     *             @OA\Property(property="start_date", type="string", format="date", example="2026-01-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2026-03-31")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Anomalías de flujo de caja detectadas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function detectCashFlowAnomalies(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $empresaId = $request->user()->empresa_id;
            $startDate = Carbon::parse($request->input('start_date'));

            $this->anomalyService->setEmpresa($empresaId);
            $result = $this->anomalyService->detectCashAnomalies($startDate);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al detectar anomalías en flujo de caja',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ai/anomalies/accounting",
     *     summary="Detectar anomalías contables",
     *     description="Detecta descuentos excesivos y patrones contables irregulares",
     *     operationId="detectAccountingAnomalies",
     *     tags={"AI - Detección de Anomalías"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"start_date", "end_date"},
     *             @OA\Property(property="start_date", type="string", format="date", example="2026-01-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2026-03-31")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Anomalías contables detectadas",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function detectAccountingAnomalies(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $empresaId = $request->user()->empresa_id;
            $startDate = Carbon::parse($request->input('start_date'));

            $this->anomalyService->setEmpresa($empresaId);
            $result = $this->anomalyService->detectDiscountAnomalies($startDate);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al detectar anomalías contables',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ai/anomalies/full-audit",
     *     summary="Ejecutar auditoría completa",
     *     description="Ejecuta un análisis integral de anomalías: ventas, flujo de caja, contabilidad y descuentos",
     *     operationId="runFullAudit",
     *     tags={"AI - Detección de Anomalías"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"start_date", "end_date"},
     *             @OA\Property(property="start_date", type="string", format="date", example="2026-01-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2026-03-31"),
     *             @OA\Property(property="include_ai_analysis", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Auditoría completa ejecutada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="audit_date", type="string", format="date-time"),
     *                 @OA\Property(property="period", type="object",
     *                     @OA\Property(property="start", type="string", format="date"),
     *                     @OA\Property(property="end", type="string", format="date")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function runFullAudit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'include_ai_analysis' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $empresaId = $request->user()->empresa_id;
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));
            $days = (int) $startDate->diffInDays($endDate);

            $this->anomalyService->setEmpresa($empresaId);
            $result = $this->anomalyService->runFullAnalysis($days);

            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'audit_date' => now()->toIso8601String(),
                    'period' => [
                        'start' => $request->input('start_date'),
                        'end' => $request->input('end_date'),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar auditoría completa',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
}
