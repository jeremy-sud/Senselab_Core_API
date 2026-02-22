<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

/**
 * Observer AuditObserver - Rastreo Automático de Auditoría FASE 3
 *
 * Captura automáticamente todos los cambios en modelos auditados
 * y registra creación, actualizaciones, eliminaciones.
 *
 * Uso: En AppServiceProvider->boot()
 *      Modelo::observe(AuditObserver::class);
 *
 * @package App\Observers
 * @version 1.0.0
 */
class AuditObserver
{
    /**
     * Campos sensibles que no deben guardarse sin enmascaramiento
     */
    /** @var array<int, string> */
    protected array $sensitiveFields = [
        'password',
        'secret',
        'token',
        'api_key',
        'credit_card',
        'ssn',
        'phone',
        'email',
        'passport',
        'license',
        'cvv',
        'pin',
    ];

    /**
     * Modelos que NO se deben auditar
     */
    /** @var array<int, string> */
    protected array $ignoredModels = [
        'PassportTokens',
        'OAuthClients',
        'ActivityLog',
        'AuditLog',
        'GdprDeletionRequest',
    ];

    /**
     * Handle: Modelo creado
     */
    public function created(Model $model): void
    {
        if ($this->shouldIgnore($model)) {
            return;
        }

        $this->createAuditLog($model, 'created', null, $model->getAttributes());
    }

    /**
     * Handle: Modelo actualizado
     */
    public function updated(Model $model): void
    {
        if ($this->shouldIgnore($model)) {
            return;
        }

        $oldValues = $model->getOriginal();
        $newValues = $model->getAttributes();

        $changed = [];
        foreach ($newValues as $key => $value) {
            if ($value !== ($oldValues[$key] ?? null)) {
                $changed[$key] = true;
            }
        }

        if (empty($changed)) {
            return; // Sin cambios reales
        }

        $this->createAuditLog($model, 'updated', $oldValues, $newValues);
    }

    /**
     * Handle: Modelo soft-delete
     */
    public function deleting(Model $model): void
    {
        if ($this->shouldIgnore($model)) {
            return;
        }

        // Solo registrar si está activado soft-delete
        if (!method_exists($model, 'trashed')) {
            return;
        }

        $this->createAuditLog($model, 'deleted', $model->getAttributes(), null);
    }

    /**
     * Handle: Modelo restaurado
     */
    public function restored(Model $model): void
    {
        if ($this->shouldIgnore($model)) {
            return;
        }

        $this->createAuditLog($model, 'restored', null, $model->getAttributes());
    }

    /**
     * Handle: Modelo eliminado permanentemente
     */
    public function forceDeleting(Model $model): void
    {
        if ($this->shouldIgnore($model)) {
            return;
        }

        $this->createAuditLog($model, 'forceDeleted', $model->getAttributes(), null);
    }

    /**
     * Crear registro de auditoría
     *
     * @param Model $model
     * @param string $action
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    protected function createAuditLog(
        Model $model,
        string $action,
        ?array $oldValues,
        ?array $newValues
    ): void {
        try {
            $user = Auth::user();
            $request = request();

            // Detectar campos sensibles
            $sensitiveFields = $this->detectSensitiveFields($oldValues, $newValues);
            $oldValues = $this->maskSensitiveValues($oldValues, $sensitiveFields);
            $newValues = $this->maskSensitiveValues($newValues, $sensitiveFields);

            // Calcular retención de datos
            $retentionDays = $this->getRetentionDays($model);
            $retentionExpiresAt = $retentionDays > 0
                ? now()->addDays($retentionDays)
                : null;

            AuditLog::create([
                'user_id' => $user?->id,
                'user_email' => $user->email ?? 'system@ursol-cast.local',
                'user_name' => $user->name ?? 'System',
                'auditable_type' => get_class($model),
                'auditable_id' => $model->getKey(),
                'action' => $action,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_method' => $request->method(),
                'request_path' => $request->path(),
                'involves_sensitive_data' => !empty($sensitiveFields),
                'sensitive_fields_mask' => $sensitiveFields ?: null,
                'retention_expires_at' => $retentionExpiresAt,
                'change_reason' => $request->header('X-Change-Reason'),
                'metadata' => [
                    'model_table' => $model->getTable(),
                    'request_id' => $request->header('X-Request-ID') ?? substr(md5(time()), 0, 8),
                    'tenant_id' => $model->tenant_id ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Error creando AuditLog: ' . $e->getMessage(), [
                'model' => get_class($model),
                'action' => $action,
                'error' => $e,
            ]);
        }
    }

    /**
     * Detectar campos sensibles
     *
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     * @return array<int, string>
     */
    protected function detectSensitiveFields(?array $oldValues, ?array $newValues): array
    {
        $sensitive = [];

        $allValues = array_merge($oldValues ?? [], $newValues ?? []);
        foreach (array_keys($allValues) as $field) {
            if ($this->isSensitiveField($field)) {
                $sensitive[] = $field;
            }
        }

        return array_unique($sensitive);
    }

    /**
     * Verificar si un campo es sensible
     */
    protected function isSensitiveField(string $field): bool
    {
        $field = strtolower($field);

        foreach ($this->sensitiveFields as $pattern) {
            if (str_contains($field, strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enmascarar valores sensibles
     *
     * @param array<string, mixed>|null $values
     * @param array<int, string> $sensitiveFields
     * @return array<string, mixed>|null
     */
    protected function maskSensitiveValues(?array $values, array $sensitiveFields): ?array
    {
        if (!$values) {
            return null;
        }

        foreach ($sensitiveFields as $field) {
            if (isset($values[$field])) {
                $values[$field] = '***MASKED***';
            }
        }

        return $values;
    }

    /**
     * Obtener días de retención para el modelo
     */
    protected function getRetentionDays(Model $model): int
    {
        // Implementar lógica basada en políticas de retención
        $policy = \App\Models\DataRetentionPolicy::firstWhere(
            'table_name',
            $model->getTable()
        );

        return $policy->retention_days ?? 365; // Default: 1 año
    }

    /**
     * Verificar si el modelo debe ser ignorado
     */
    protected function shouldIgnore(Model $model): bool
    {
        $modelClass = class_basename($model);

        foreach ($this->ignoredModels as $ignored) {
            if (Str::contains($modelClass, $ignored)) {
                return true;
            }
        }

        return false;
    }
}
