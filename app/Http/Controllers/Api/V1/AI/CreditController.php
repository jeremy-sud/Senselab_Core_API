<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\AI\CreditScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Annotations as OA;

/**
 * Controller para scoring de crédito de clientes mediante IA
 *
 * @OA\Tag(
 *     name="AI - Credit Scoring",
 *     description="Endpoints para análisis de riesgo crediticio y scoring de clientes mediante IA"
 * )
 */
class CreditController extends Controller
{
    public function __construct(
        private CreditScoringService $creditService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/v1/ai/credit/{clienteId}/score",
     *     summary="Calcular score de crédito",
     *     description="Calcula el score de crédito (0-100) de un cliente usando análisis de IA",
     *     operationId="calculateCreditScore",
     *     tags={"AI - Credit Scoring"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clienteId",
     *         in="path",
     *         required=true,
     *         description="ID del cliente",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Score calculado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="score", type="integer", example=75),
     *                 @OA\Property(property="risk_level", type="string", example="medium"),
     *                 @OA\Property(property="factors", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Cliente no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function calculateScore(Request $request, int $clienteId): JsonResponse
    {
        try {
            $empresaId = $request->user()->empresa_id;

            // Validar que el cliente pertenece a la empresa del usuario
            Cliente::where('empresa_id', $empresaId)->findOrFail($clienteId);

            $this->creditService->setEmpresa($empresaId);
            $result = $this->creditService->calculateScore($clienteId);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular score de crédito',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ai/credit/{clienteId}/analysis",
     *     summary="Análisis detallado de crédito",
     *     description="Obtiene análisis de crédito detallado con factores de riesgo desglosados",
     *     operationId="getDetailedCreditAnalysis",
     *     tags={"AI - Credit Scoring"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clienteId",
     *         in="path",
     *         required=true,
     *         description="ID del cliente",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Análisis detallado generado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="detailed", type="boolean", example=true),
     *                 @OA\Property(property="analyzed_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Cliente no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getDetailedAnalysis(Request $request, int $clienteId): JsonResponse
    {
        try {
            $empresaId = $request->user()->empresa_id;

            // Validar que el cliente pertenece a la empresa del usuario
            Cliente::where('empresa_id', $empresaId)->findOrFail($clienteId);

            $this->creditService->setEmpresa($empresaId);
            $result = $this->creditService->calculateScore($clienteId);

            // Enriquecer con análisis
            $result['detailed'] = true;
            $result['analyzed_at'] = now()->toIso8601String();

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener análisis detallado',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ai/credit/{clienteId}/limit",
     *     summary="Recomendar límite de crédito",
     *     description="Simula y recomienda un límite de crédito apropiado para el cliente",
     *     operationId="recommendCreditLimit",
     *     tags={"AI - Credit Scoring"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="clienteId",
     *         in="path",
     *         required=true,
     *         description="ID del cliente",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Límite de crédito recomendado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="recommended_limit", type="number", format="float"),
     *                 @OA\Property(property="risk_assessment", type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=404, description="Cliente no encontrado"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function recommendCreditLimit(Request $request, int $clienteId): JsonResponse
    {
        try {
            $empresaId = $request->user()->empresa_id;

            $this->creditService->setEmpresa($empresaId);

            // Simular con un monto base
            $result = $this->creditService->simulateCredit($clienteId, 500000, 30);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al recomendar límite de crédito',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ai/credit/batch",
     *     summary="Calcular scores masivo",
     *     description="Calcula scores de crédito para múltiples clientes en lote (máx 100)",
     *     operationId="batchCalculateCreditScore",
     *     tags={"AI - Credit Scoring"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cliente_ids"},
     *             @OA\Property(property="cliente_ids", type="array", minItems=1, maxItems=100,
     *                 @OA\Items(type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Scores calculados",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="results", type="array", @OA\Items(type="object")),
     *                 @OA\Property(property="processed", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function batchCalculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cliente_ids' => 'required|array|min:1|max:100',
            'cliente_ids.*' => 'integer|exists:clientes,id',
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
            $this->creditService->setEmpresa($empresaId);

            $results = [];
            $clienteIds = $request->input('cliente_ids');

            foreach ($clienteIds as $clienteId) {
                try {
                    $score = $this->creditService->calculateScore($clienteId);
                    $results[] = array_merge(['cliente_id' => $clienteId], $score);
                } catch (\Exception $e) {
                    $results[] = [
                        'cliente_id' => $clienteId,
                        'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'results' => $results,
                    'processed' => count($results),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular scores masivos',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/ai/credit/ranking",
     *     summary="Ranking de clientes por score",
     *     description="Obtiene ranking de clientes ordenado por score de crédito, con filtro opcional por nivel de riesgo",
     *     operationId="getCreditRanking",
     *     tags={"AI - Credit Scoring"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="integer", minimum=1, maximum=100, default=20)
     *     ),
     *     @OA\Parameter(
     *         name="risk_level",
     *         in="query",
     *         required=false,
     *         description="Filtrar por nivel de riesgo",
     *         @OA\Schema(type="string", enum={"low", "medium", "high"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ranking obtenido",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function getRanking(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order' => 'nullable|in:asc,desc',
            'limit' => 'nullable|integer|min:1|max:100',
            'risk_level' => 'nullable|in:low,medium,high',
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
            $limit = $request->input('limit', 20);
            $riskLevel = $request->input('risk_level');

            $this->creditService->setEmpresa($empresaId);

            if ($riskLevel === 'high') {
                $result = $this->creditService->getHighRiskClients($limit);
            } else {
                $result = $this->creditService->calculateAllScores($limit);
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ranking',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/v1/ai/credit/evaluate-transaction",
     *     summary="Evaluar riesgo de transacción",
     *     description="Evalúa el riesgo de una transacción específica simulando el crédito del cliente",
     *     operationId="evaluateCreditTransaction",
     *     tags={"AI - Credit Scoring"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"cliente_id", "amount"},
     *             @OA\Property(property="cliente_id", type="integer", example=1),
     *             @OA\Property(property="amount", type="number", format="float", minimum=0, example=150000),
     *             @OA\Property(property="payment_terms", type="integer", minimum=0, maximum=365, example=30)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Riesgo evaluado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="approved", type="boolean"),
     *                 @OA\Property(property="risk_score", type="number", format="float")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="No autenticado"),
     *     @OA\Response(response=422, description="Datos de validación inválidos"),
     *     @OA\Response(response=500, description="Error interno del servidor")
     * )
     */
    public function evaluateTransaction(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cliente_id' => 'required|integer|exists:clientes,id',
            'amount' => 'required|numeric|min:0',
            'payment_terms' => 'nullable|integer|min:0|max:365',
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

            $this->creditService->setEmpresa($empresaId);

            $result = $this->creditService->simulateCredit(
                $request->input('cliente_id'),
                (float) $request->input('amount'),
                $request->input('payment_terms', 30)
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al evaluar transacción',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor',
            ], 500);
        }
    }
}
