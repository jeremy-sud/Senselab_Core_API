<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\GdprDeletionRequest;
use App\Models\DataRetentionPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Controller: ComplianceDashboardController - Dashboard de Compliance GDPR
 *
 * Proporciona métricas, reportes y estadísticas sobre:
 * - Cumplimiento GDPR
 * - Auditoría de cambios
 * - Retención de datos
 * - Solicitudes de derecho al olvido
 *
 * @package App\Http\Controllers
 * @version 1.0.0 - FASE 3
 */
class ComplianceDashboardController extends Controller
{
    /**
     * Middleware de autorización
     */
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('can:view compliance dashboard');
    }

    /**
     * GET /api/compliance/dashboard
     * Panel principal de compliance
     */
    public function getDashboard(Request $request): JsonResponse
    {
        try {
            $summary = [
                'audit_logs' => $this->getAuditSummary(),
                'gdpr_requests' => $this->getGdprSummary(),
                'retention_policies' => $this->getRetentionSummary(),
                'data_protection' => $this->getDataProtectionStatus(),
                'recent_sensitive_changes' => $this->getRecentSensitiveChanges(),
            ];

            return response()->json([
                'success' => true,
                'dashboard' => $summary,
                'timestamp' => Carbon::now()->toIso8601String(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo dashboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/compliance/audit-logs
     * Audit logs con filtros complejos
     */
    public function getAuditLogs(Request $request): JsonResponse
    {
        try {
            $query = AuditLog::query();

            // Filtros
            if ($request->has('action')) {
                $query->byAction($request->input('action'));
            }

            if ($request->has('user_id')) {
                $query->byUser($request->input('user_id'));
            }

            if ($request->has('model_type')) {
                $query->forModel($request->input('model_type'));
            }

            if ($request->has('sensitive_only') && $request->boolean('sensitive_only')) {
                $query->sensitiveOnly();
            }

            if ($request->has('date_from') && $request->has('date_to')) {
                $from = Carbon::parse($request->input('date_from'));
                $to = Carbon::parse($request->input('date_to'));
                $query->dateRange($from, $to);
            }

            if ($request->has('ip')) {
                $query->byIp($request->input('ip'));
            }

            $logs = $query->recent()
                         ->paginate($request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'pagination' => [
                    'total' => $logs->total(),
                    'per_page' => $logs->perPage(),
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo logs: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/compliance/audit-logs/:id
     * Detalle de un audit log específico
     */
    public function getAuditLogDetail(string $id): JsonResponse
    {
        try {
            $log = AuditLog::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $log->id,
                    'summary' => $log->getSummary(),
                    'changes' => $log->getReadableChanges(),
                    'user' => [
                        'id' => $log->user_id,
                        'email' => $log->user_email,
                        'name' => $log->user_name,
                    ],
                    'context' => [
                        'ip' => $log->ip_address,
                        'user_agent' => $log->user_agent,
                        'method' => $log->request_method,
                        'path' => $log->request_path,
                    ],
                    'sensitive_data' => $log->involves_sensitive_data,
                    'sensitive_fields' => $log->sensitive_fields_mask,
                    'retention_expires' => $log->retention_expires_at?->toIso8601String(),
                    'metadata' => $log->metadata,
                    'created_at' => $log->created_at?->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Log no encontrado'], 404);
        }
    }

    /**
     * GET /api/compliance/retention-policies
     * Listar políticas de retención
     */
    public function getRetentionPolicies(Request $request): JsonResponse
    {
        try {
            $policies = DataRetentionPolicy::with('creator')
                                          ->paginate($request->input('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => $policies->items(),
                'pagination' => [
                    'total' => $policies->total(),
                    'per_page' => $policies->perPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo políticas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/compliance/retention-policies/:id
     * Detalle de política de retención
     */
    public function getRetentionPolicyDetail(string $id): JsonResponse
    {
        try {
            $policy = DataRetentionPolicy::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'description' => $policy->description,
                    'configuration' => $policy->getConfigurationJson(),
                    'statistics' => [
                        'last_execution' => $policy->last_execution_at?->toIso8601String(),
                        'rows_affected_last_run' => $policy->rows_affected,
                        'last_error' => $policy->last_error,
                    ],
                    'creator' => $policy->creator?->only(['id', 'email', 'name']),
                    'created_at' => $policy->created_at?->toIso8601String(),
                    'updated_at' => $policy->updated_at?->toIso8601String(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Política no encontrada'], 404);
        }
    }

    /**
     * POST /api/compliance/retention-policies/:id/execute
     * Ejecutar política de retención manualmente
     */
    public function executeRetentionPolicy(string $id, Request $request): JsonResponse
    {
        try {
            $policy = DataRetentionPolicy::findOrFail($id);

            if (!$policy->enabled) {
                return response()->json([
                    'success' => false,
                    'message' => 'Política deshabilitada',
                ], 400);
            }

            $result = $policy->execute();

            // Registrar en auditoría
            AuditLog::create([
                'user_id' => Auth::id(),
                'user_email' => Auth::user()->email,
                'user_name' => Auth::user()->name,
                'auditable_type' => DataRetentionPolicy::class,
                'auditable_id' => $policy->id,
                'action' => 'updated',
                'new_values' => ['last_execution_at' => Carbon::now()],
                'ip_address' => $request->ip(),
                'request_method' => 'POST',
                'request_path' => "api/compliance/retention-policies/{$id}/execute",
                'change_reason' => "Política de retención ejecutada manualmente por {$request->user()->email}",
            ]);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'details' => [
                    'action' => $result['action'] ?? null,
                    'affected_rows' => $result['affected'] ?? 0,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error ejecutando política: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/compliance/report/gdpr
     * Reporte de cumplimiento GDPR
     */
    public function getGdprComplianceReport(Request $request): JsonResponse
    {
        try {
            $from = Carbon::parse($request->input('date_from', Carbon::now()->subMonths(3)));
            $to = Carbon::parse($request->input('date_to', Carbon::now()));

            $report = [
                'period' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'deletion_requests' => [
                    'total' => GdprDeletionRequest::whereBetween('created_at', [$from, $to])->count(),
                    'completed' => GdprDeletionRequest::where('status', 'completed')
                                                      ->whereBetween('completed_at', [$from, $to])
                                                      ->count(),
                    'pending' => GdprDeletionRequest::where('status', 'pending')
                                                    ->whereBetween('created_at', [$from, $to])
                                                    ->count(),
                    'average_processing_time_days' => $this->getAverageProcessingTime($from, $to),
                ],
                'sensitive_data_access' => [
                    'total_accesses' => AuditLog::sensitiveOnly()
                                               ->whereBetween('created_at', [$from, $to])
                                               ->count(),
                    'by_action' => AuditLog::sensitiveOnly()
                                          ->whereBetween('created_at', [$from, $to])
                                          ->select('action', DB::raw('count(*) as count'))
                                          ->groupBy('action')
                                          ->pluck('count', 'action'),
                ],
                'data_retention_compliance' => [
                    'policies_count' => DataRetentionPolicy::active()->count(),
                    'last_executions' => DataRetentionPolicy::whereNotNull('last_execution_at')
                                                             ->recent()
                                                             ->limit(5)
                                                             ->get(['name', 'last_execution_at', 'rows_affected']),
                ],
                'user_activity' => [
                    'active_users_with_sensitive_access' => AuditLog::sensitiveOnly()
                                                                   ->whereBetween('created_at', [$from, $to])
                                                                   ->select('user_id', 'user_email')
                                                                   ->distinct()
                                                                   ->count(),
                ],
            ];

            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error generando reporte: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ════════════════════════════════════════════════════════════════
     * MÉTODOS PRIVADOS - Helpers de Resumen
     * ════════════════════════════════════════════════════════════════
     */

    /**
     * @return array<string, mixed>
     */
    private function getAuditSummary(): array
    {
        $thirtyDays = Carbon::now()->subDays(30);

        return [
            'total_logs' => AuditLog::count(),
            'sensitive_changes' => AuditLog::sensitiveOnly()->count(),
            'last_30_days' => AuditLog::where('created_at', '>=', $thirtyDays)->count(),
            'by_action' => AuditLog::select('action', DB::raw('count(*) as count'))
                                   ->groupBy('action')
                                   ->pluck('count', 'action'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getGdprSummary(): array
    {
        return [
            'total_requests' => GdprDeletionRequest::count(),
            'pending' => GdprDeletionRequest::pending()->count(),
            'approved' => GdprDeletionRequest::approved()->count(),
            'completed' => GdprDeletionRequest::completed()->count(),
            'failed' => GdprDeletionRequest::where('status', 'failed')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getRetentionSummary(): array
    {
        return [
            'total_policies' => DataRetentionPolicy::count(),
            'active_policies' => DataRetentionPolicy::active()->count(),
            'auto_execute_policies' => DataRetentionPolicy::autoExecute()->count(),
            'policies_with_errors' => DataRetentionPolicy::withErrors()->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getDataProtectionStatus(): array
    {
        return [
            'timestamp' => Carbon::now()->toIso8601String(),
            'audit_logs_active' => AuditLog::count() > 0,
            'gdpr_ready' => GdprDeletionRequest::count() > 0,
            'retention_policies_active' => DataRetentionPolicy::active()->count() > 0,
            'encryption_status' => 'configured', // Verificar si hay usuarios con campos encriptados
        ];
    }

    /**
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private function getRecentSensitiveChanges(int $limit = 10): array
    {
        return AuditLog::sensitiveOnly()
                       ->recent()
                       ->limit($limit)
                       ->get()
                       ->map(fn ($log) => [
                           'id' => $log->id,
                           'summary' => $log->getSummary(),
                           'user' => $log->user_email,
                           'timestamp' => $log->created_at?->toIso8601String(),
                       ])
                       ->toArray();
    }

    private function getAverageProcessingTime(Carbon $from, Carbon $to): string
    {
        $requests = GdprDeletionRequest::where('status', 'completed')
                                      ->whereBetween('completed_at', [$from, $to])
                                      ->get();

        if ($requests->isEmpty()) {
            return 'N/A';
        }

        $totalDays = 0;
        foreach ($requests as $request) {
            $diff = $request->completed_at->diffInDays($request->created_at);
            $totalDays += $diff;
        }

        $average = intval($totalDays / $requests->count());
        return "{$average} días";
    }
}
