<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DataRetentionPolicy;
use App\Models\GdprDeletionRequest;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para el dashboard de Compliance/GDPR.
 *
 * Encapsula la lógica de agregación de métricas, reportes
 * y estadísticas de cumplimiento.
 */
class ComplianceDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function getDashboardSummary(): array
    {
        return [
            'audit_logs' => $this->getAuditSummary(),
            'gdpr_requests' => $this->getGdprSummary(),
            'retention_policies' => $this->getRetentionSummary(),
            'data_protection' => $this->getDataProtectionStatus(),
            'recent_sensitive_changes' => $this->getRecentSensitiveChanges(),
        ];
    }

    /**
     * @param array<string, mixed> $filtros
     */
    public function getAuditLogs(array $filtros = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AuditLog::query();

        if (!empty($filtros['action'])) {
            $query->byAction($filtros['action']);
        }

        if (!empty($filtros['user_id'])) {
            $query->byUser($filtros['user_id']);
        }

        if (!empty($filtros['model_type'])) {
            $query->forModel($filtros['model_type']);
        }

        if (!empty($filtros['sensitive_only'])) {
            $query->sensitiveOnly();
        }

        if (!empty($filtros['date_from']) && !empty($filtros['date_to'])) {
            $from = Carbon::parse($filtros['date_from']);
            $to = Carbon::parse($filtros['date_to']);
            $query->dateRange($from, $to);
        }

        if (!empty($filtros['ip'])) {
            $query->byIp($filtros['ip']);
        }

        return $query->recent()->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAuditLogDetail(string $id): array
    {
        $log = AuditLog::findOrFail($id);

        return [
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
        ];
    }

    public function getRetentionPolicies(int $perPage = 10): LengthAwarePaginator
    {
        return DataRetentionPolicy::with('creator')->paginate($perPage);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRetentionPolicyDetail(string $id): array
    {
        $policy = DataRetentionPolicy::findOrFail($id);

        return [
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function executeRetentionPolicy(string $id): array
    {
        $policy = DataRetentionPolicy::findOrFail($id);

        if (!$policy->enabled) {
            throw new \App\Exceptions\BusinessException('Política deshabilitada');
        }

        return $policy->execute();
    }

    /**
     * @return array<string, mixed>
     */
    public function getGdprComplianceReport(Carbon $from, Carbon $to): array
    {
        return [
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
    }

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
            'encryption_status' => 'configured',
        ];
    }

    /**
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
