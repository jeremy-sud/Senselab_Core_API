<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo AuditLog - Auditoría Completa de Cambios
 *
 * Almacena todos los eventos de create, update, delete, etc.
 * en registros auditados. Proporciona trazabilidad completa de cambios.
 *
 * @package App\Models
 * @version 1.0.0
 */
class AuditLog extends Model
{
    const UPDATED_AT = null; // Auditoría nunca se actualiza, solo se crea

    protected $table = 'audit_logs';

    protected $fillable = [
        'event',
        'model_type',
        'model_id',
        'model_name',
        'user_id',
        'user_email',
        'user_name',
        'old_values',
        'new_values',
        'changed_fields',
        'ip_address',
        'user_agent',
        'http_method',
        'url',
        'route_action',
        'empresa_id',
        'tenant_id',
        'description',
        'metadata',
        'execution_time_ms',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Relación: Usuario que realizó el cambio
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    /**
     * Relación: Empresa del cambio
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Scope: Filtrar por tipo de evento
     */
    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope: Filtrar por modelo
     */
    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        $query = $query->where('model_type', $modelType);
        
        if ($modelId !== null) {
            $query = $query->where('model_id', $modelId);
        }

        return $query;
    }

    /**
     * Scope: Filtrar por usuario
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filtrar por empresa
     */
    public function scopeByEmpresa($query, int $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope: Filtrar por rango de fechas
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Filtrar por IP
     */
    public function scopeByIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope: Filtrar por cambios en campo específico
     */
    public function scopeChangedField($query, string $field)
    {
        return $query->whereJsonContains('changed_fields', $field);
    }

    /**
     * Scope: Solo eventos críticos (create, delete)
     */
    public function scopeCritical($query)
    {
        return $query->whereIn('event', ['created', 'deleted']);
    }

    /**
     * Scope: Búsqueda de texto completo
     */
    public function scopeSearch($query, string $term)
    {
        return $query->whereFullText(['description'], $term);
    }

    /**
     * Obtener descripción legible del cambio
     *
     * @return string
     */
    public function getReadableDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $event = strtoupper($this->event);
        $model = class_basename($this->model_type);

        return "{$event} {$model} #{$this->model_id} by {$this->user_email}";
    }

    /**
     * Obtener los campos que cambiaron con sus valores old/new
     *
     * @return array
     */
    public function getFieldChangesAttribute(): array
    {
        if (! $this->changed_fields) {
            return [];
        }

        $changes = [];

        foreach ($this->changed_fields as $field) {
            $changes[$field] = [
                'old' => $this->old_values[$field] ?? null,
                'new' => $this->new_values[$field] ?? null,
            ];
        }

        return $changes;
    }

    /**
     * Verificar si fue un cambio sensible (en campos sensibles)
     *
     * @return bool
     */
    public function isSensitiveChange(): bool
    {
        $sensitivePatterns = config('audit.changes.sensitive_patterns', []);

        foreach ($this->changed_fields ?? [] as $field) {
            foreach ($sensitivePatterns as $pattern) {
                if (str_contains(strtolower($field), strtolower($pattern))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Obtener un resumen JSON del cambio
     *
     * @return array
     */
    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'model' => [
                'type' => $this->model_type,
                'id' => $this->model_id,
                'name' => $this->model_name,
            ],
            'user' => [
                'id' => $this->user_id,
                'email' => $this->user_email,
                'name' => $this->user_name,
            ],
            'changes' => $this->field_changes,
            'timestamp' => $this->created_at?->toIso8601String(),
            'ip' => $this->ip_address,
            'is_sensitive' => $this->isSensitiveChange(),
        ];
    }

    /**
     * Obtener la URL del modelo auditado
     *
     * @return string|null
     */
    public function getModelUrlAttribute(): ?string
    {
        if (! $this->model_id) {
            return null;
        }

        $path = str_ireplace('App\\Models\\', '', $this->model_type);
        $path = strtolower(preg_replace('/([A-Z])/', '-$1', $path));
        $path = ltrim($path, '-');

        return route("api.v1.{$path}.show", $this->model_id) ?? null;
    }

    /**
     * Formatear para API response
     *
     * @return array
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'model_type' => $this->model_type,
            'model_id' => $this->model_id,
            'model_name' => $this->model_name,
            'user' => [
                'id' => $this->user_id,
                'email' => $this->user_email,
                'name' => $this->user_name,
            ],
            'old_values' => $this->old_values,
            'new_values' => $this->new_values,
            'changed_fields' => $this->changed_fields,
            'context' => [
                'ip_address' => $this->ip_address,
                'user_agent' => $this->user_agent,
                'http_method' => $this->http_method,
                'url' => $this->url,
                'route_action' => $this->route_action,
            ],
            'metadata' => $this->metadata,
            'execution_time_ms' => $this->execution_time_ms,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
