<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Servicio de Auditoría Completa
 *
 * Proporciona métodos estáticos para grabar eventos de auditoría.
 * Integración automática con modelos Eloquent mediante listeners.
 *
 * @package App\Services
 * @version 1.0.0
 */
class AuditService
{
    /**
     * Registrar un evento de auditoría
     *
     * @param string $event Tipo de evento (created, updated, deleted, etc.)
     * @param Model $model Modelo afectado
     * @param array|null $oldValues Valores anteriores (para updated/deleted)
     * @param array|null $newValues Valores nuevos (para created/updated)
     * @return \App\Models\AuditLog
     */
    public static function record(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null
    ): AuditLog {
        if (! config('audit.enabled')) {
            return new AuditLog();
        }

        // Verificar si este modelo debe auditarse
        if (! static::shouldAudit($model::class, $event)) {
            return new AuditLog();
        }

        $user = Auth::user();
        $request = request();

        // Calcular campos que cambiaron
        $changedFields = [];
        if ($oldValues && $newValues) {
            $changedFields = array_keys(array_filter(
                $newValues,
                fn($value, $key) => ($oldValues[$key] ?? null) !== $value,
                ARRAY_FILTER_USE_BOTH
            ));
        }

        // Enmascarar valores sensibles
        if (config('audit.changes.mask_sensitive_values')) {
            $oldValues = static::maskSensitiveValues($oldValues);
            $newValues = static::maskSensitiveValues($newValues);
        }

        // Limitar tamaño de campos
        $maxSize = config('audit.changes.max_field_size', 500);
        if ($oldValues) {
            $oldValues = static::truncateValues($oldValues, $maxSize);
        }
        if ($newValues) {
            $newValues = static::truncateValues($newValues, $maxSize);
        }

        // Crear log de auditoría
        $audit = AuditLog::create([
            'event' => $event,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'model_name' => $model->__toString(),
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_name' => $user?->name,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => ! empty($changedFields) ? $changedFields : null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'http_method' => $request->method(),
            'url' => $request->url(),
            'route_action' => $request->route()?->getActionName(),
            'empresa_id' => $model->empresa_id ?? $user?->empresa_id,
            'tenant_id' => $model->tenant_id ?? null,
            'description' => static::generateDescription($event, $model, $changedFields),
            'metadata' => [],
            'execution_time_ms' => null,
        ]);

        // Loguear evento crítico
        if (static::isCriticalEvent($event, $model)) {
            static::logCriticalEvent($audit, $changedFields);
        }

        return $audit;
    }

    /**
     * Verificar si un modelo debe auditarse
     *
     * @param string $modelClass Clase del modelo
     * @param string $event Tipo de evento
     * @return bool
     */
    public static function shouldAudit(string $modelClass, string $event): bool
    {
        $auditedModels = config('audit.models', []);

        // Si no hay configuración específica del modelo
        if (! isset($auditedModels[$modelClass])) {
            // Auditar todos los modelos por defecto
            return true;
        }

        $config = $auditedModels[$modelClass];

        // Modelo deshabilitado
        if (! ($config['enabled'] ?? true)) {
            return false;
        }

        // Evento no auditado para este modelo
        $events = $config['events'] ?? config('audit.events', []);
        if (! ($events[$event] ?? true)) {
            return false;
        }

        return true;
    }

    /**
     * Enmascarar valores sensibles
     *
     * @param array|null $values
     * @return array|null
     */
    protected static function maskSensitiveValues(?array $values): ?array
    {
        if (! $values) {
            return null;
        }

        $sensitivePatterns = config('audit.changes.sensitive_patterns', []);
        $maskValue = config('audit.changes.masked_value', '***MASKED***');
        $masked = $values;

        foreach ($values as $key => $value) {
            foreach ($sensitivePatterns as $pattern) {
                if (str_contains(strtolower($key), strtolower($pattern))) {
                    $masked[$key] = $maskValue;
                    break;
                }
            }
        }

        return $masked;
    }

    /**
     * Truncar valores muy grandes
     *
     * @param array|null $values
     * @param int $maxSize
     * @return array|null
     */
    protected static function truncateValues(?array $values, int $maxSize): ?array
    {
        if (! $values) {
            return null;
        }

        $truncated = $values;

        foreach ($truncated as $key => $value) {
            if (is_string($value) && strlen($value) > $maxSize) {
                $truncated[$key] = substr($value, 0, $maxSize) . '...';
            }
        }

        return $truncated;
    }

    /**
     * Generar descripción legible del cambio
     *
     * @param string $event
     * @param Model $model
     * @param array $changedFields
     * @return string
     */
    protected static function generateDescription(string $event, Model $model, array $changedFields): string
    {
        $user = Auth::user();
        $userName = $user?->name ?? 'Unknown User';
        $modelName = class_basename($model::class);

        return match($event) {
            'created' => "{$userName} created {$modelName} #{$model->id}",
            'updated' => "{$userName} updated {$modelName} #{$model->id}" . 
                        (! empty($changedFields) ? ": " . implode(', ', $changedFields) : ""),
            'deleted' => "{$userName} deleted {$modelName} #{$model->id}",
            'restored' => "{$userName} restored {$modelName} #{$model->id}",
            default => "{$userName} {$event} {$modelName} #{$model->id}",
        };
    }

    /**
     * Verificar si es un evento crítico
     *
     * @param string $event
     * @param Model $model
     * @return bool
     */
    protected static function isCriticalEvent(string $event, Model $model): bool
    {
        if (! in_array($event, config('audit.events', []))) {
            return false;
        }

        $criticalModels = config('audit.logging.critical_models', []);

        return in_array($model::class, $criticalModels);
    }

    /**
     * Loguear evento crítico
     *
     * @param AuditLog $audit
     * @param array $changedFields
     * @return void
     */
    protected static function logCriticalEvent(AuditLog $audit, array $changedFields): void
    {
        $channel = config('audit.logging.channel', 'audit');

        Log::channel($channel)->warning("Critical audit event: {$audit->event}", [
            'audit_id' => $audit->id,
            'model' => $audit->model_type,
            'model_id' => $audit->model_id,
            'user_id' => $audit->user_id,
            'changed_fields' => $changedFields,
            'timestamp' => $audit->created_at,
        ]);

        // Notificar si está habilitado
        if (config('audit.logging.notify_critical_changes')) {
            static::notifyCriticalChange($audit);
        }
    }

    /**
     * Notificar cambio crítico a administradores
     *
     * @param AuditLog $audit
     * @return void
     */
    protected static function notifyCriticalChange($audit): void
    {
        // Implementación de notificación (Slack, email, etc.)
        // Esto es un placeholder para futura extensión
        Log::info("Critical change notification triggered for audit #{$audit->id}");
    }

    /**
     * Obtener auditoría de un modelo
     *
     * @param string $modelClass
     * @param int $modelId
     * @param string|null $event
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getModelAudit(string $modelClass, int $modelId, ?string $event = null)
    {
        $query = AuditLog::forModel($modelClass, $modelId);

        if ($event) {
            $query = $query->event($event);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Obtener auditoría por usuario
     *
     * @param int $userId
     * @param string|null $startDate
     * @param string|null $endDate
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getUserAudit(int $userId, ?string $startDate = null, ?string $endDate = null)
    {
        $query = AuditLog::byUser($userId);

        if ($startDate && $endDate) {
            $query = $query->dateRange($startDate, $endDate);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Obtener auditoría por empresa
     *
     * @param int $empresaId
     * @param string|null $event
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getEmpresaAudit(int $empresaId, ?string $event = null)
    {
        $query = AuditLog::byEmpresa($empresaId);

        if ($event) {
            $query = $query->event($event);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Obtener cambios de un campo específico
     *
     * @param string $field
     * @param int|null $empresaId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getFieldChanges(string $field, ?int $empresaId = null)
    {
        $query = AuditLog::changedField($field);

        if ($empresaId) {
            $query = $query->byEmpresa($empresaId);
        }

        return $query->orderByDesc('created_at')->get();
    }

    /**
     * Buscar en auditoría
     *
     * @param string $term
     * @param int|null $empresaId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function search(string $term, ?int $empresaId = null)
    {
        $query = AuditLog::search($term);

        if ($empresaId) {
            $query = $query->byEmpresa($empresaId);
        }

        return $query->limit(config('audit.search.max_results', 1000))->get();
    }

    /**
     * Obtener estadísticas de auditoría
     *
     * @param int|null $empresaId
     * @return array
     */
    public static function getStatistics(?int $empresaId = null): array
    {
        $query = AuditLog::query();

        if ($empresaId) {
            $query = $query->byEmpresa($empresaId);
        }

        return [
            'total_events' => $query->count(),
            'events_by_type' => $query->groupBy('event')->selectRaw('event, count(*) as count')->pluck('count', 'event'),
            'events_by_model' => $query->groupBy('model_type')->selectRaw('model_type, count(*) as count')->pluck('count', 'model_type'),
            'recent_events' => $query->latest('created_at')->limit(10)->get()->map->toSummary(),
            'critical_events' => $query->critical()->count(),
        ];
    }

    /**
     * Purgar auditoría antigua
     *
     * @return int Número de registros purgados
     */
    public static function purgeOldLogs(): int
    {
        $days = config('audit.retention.purge_after_days');

        if (! $days) {
            return 0;
        }

        $date = now()->subDays($days);

        return AuditLog::where('created_at', '<', $date)->delete();
    }

    /**
     * Obtener config de auditoría completa
     *
     * @return array
     */
    public static function getAuditConfig(): array
    {
        return config('audit', []);
    }

    /**
     * Verificar si auditoría está habilitada
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return config('audit.enabled', true);
    }
}
