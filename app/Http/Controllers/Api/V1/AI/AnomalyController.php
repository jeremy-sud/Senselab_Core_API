<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\AnomalyDetectionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Controller para detección de anomalías financieras mediante IA
 *
 * Proporciona endpoints para detectar transacciones sospechosas,
 * anomalías en ventas, flujo de caja y errores contables.
 *
 * @group AI - Detección de Anomalías
 */
class AnomalyController extends Controller
{
    public function __construct(
        private AnomalyDetectionService $anomalyService
    ) {}

    /**
     * Detectar anomalías en ventas
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
     * Detectar anomalías en flujo de caja
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
     * Detectar anomalías contables (descuentos excesivos)
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
     * Ejecutar auditoría completa
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
