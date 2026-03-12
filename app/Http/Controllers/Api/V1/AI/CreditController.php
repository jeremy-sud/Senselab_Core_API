<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Services\AI\CreditScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para scoring de crédito de clientes mediante IA
 *
 * @group AI - Credit Scoring
 */
class CreditController extends Controller
{
    public function __construct(
        private CreditScoringService $creditService
    ) {}

    /**
     * Calcular score de crédito
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
     * Obtener análisis detallado (alias de calculateScore con más detalles)
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
     * Recomendar límite de crédito (simulación)
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
     * Calcular scores masivo
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
     * Obtener ranking de clientes
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
     * Evaluar riesgo de transacción (simulación de crédito)
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
