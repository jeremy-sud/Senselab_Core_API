<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Modelo AuditLog - Auditoría Completa FASE 3
 *
 * Almacena todos los eventos en registros auditados inmutables.
 * Proporciona trazabilidad completa de cambios para compliance GDPR.
 * Registro es append-only (UPDATED_AT = null).
 *
 * @package App\Models
 * @version 3.0.0 - FASE 3 Compliance
 */
class AuditLog extends Model
{
    const UPDATED_AT = null; // Auditoría es immutable, solo created_at

    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'user_email',
        'user_name',
        'auditable_type',
        'auditable_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'request_method',
        'request_path',
        'involves_sensitive_data',
        'sensitive_fields_mask',
        'retention_expires_at',
        'is_archived',
        'archived_at',
        'change_reason',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'sensitive_fields_mask' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'retention_expires_at' => 'datetime',
        'archived_at' => 'datetime',
        'involves_sensitive_data' => 'boolean',
        'is_archived' => 'boolean',
    ];

    /**
     * Relación: Usuario que realizó el cambio
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener la entidad auditada (polymorphic)
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    // ════════════════════════════════════════════════════════════════
    // SCOPES - Filtrado Avanzado
    // ════════════════════════════════════════════════════════════════

    /**
     * Scope: Registros recientes
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope: Solo cambios de datos sensibles
     */
    public function scopeSensitiveOnly($query)
    {
        return $query->where('involves_sensitive_data', true);
    }

    /**
     * Scope: Registros vencidos para retención
     */
    public function scopeExpiredRetention($query)
    {
        return $query->where('retention_expires_at', '<=', Carbon::now())
                     ->where('is_archived', false);
    }

    /**
     * Scope: Por usuario
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Por modelo
     */
    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        $query->where('auditable_type', $modelType);
        
        if ($modelId) {
            $query->where('auditable_id', $modelId);
        }
        
        return $query;
    }

    /**
     * Scope: Por acción
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Rango de fechas
     */
    public function scopeDateRange($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Scope: Por dirección IP
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    // ════════════════════════════════════════════════════════════════
    // MÉTODOS - Análisis y Transformación
    // ════════════════════════════════════════════════════════════════

    /**
     * Obtener cambios legibles antes/después
     */
    public function getReadableChanges(): array
    {
        return [
            'before' => $this->old_values ?? [],
            'after' => $this->new_values ?? [],
            'changed_fields' => $this->getChangedFields(),
        ];
    }

    /**
     * Obtener solo campos que cambiaron
     */
    public function getChangedFields(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changed = [];
        foreach ($this->new_values as $field => $newValue) {
            $oldValue = $this->old_values[$field] ?? null;
            if ($oldValue !== $newValue) {
                $changed[$field] = [
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }
        }

        return $changed;
    }

    /**
     * Obtener resumen legible de la acción para reportes
     */
    public function getSummary(): string
    {
        $userName = $this->user_name ?? 'System';
        $summary = "{$userName} ";
        
        switch ($this->action) {
            case 'created':
                $summary .= "creó un nuevo {$this->auditable_type}";
                break;
            case 'updated':
                $changed = count($this->getChangedFields());
                $summary .= "actualizó {$changed} campo(s) en {$this->auditable_type}";
                break;
            case 'deleted':
                $summary .= "eliminó {$this->auditable_type}";
                break;
            case 'restored':
                $summary .= "restauró {$this->auditable_type}";
                break;
            case 'forceDeleted':
                $summary .= "eliminó permanentemente {$this->auditable_type}";
                break;
        }

        if ($this->involves_sensitive_data) {
            $summary .= " [SENSIBLE]";
        }

        return $summary;
    }

    /**
     * Enmascarar valores sensibles para la visualización
     */
    public function maskSensitiveValues(): void
    {
        if (!$this->involves_sensitive_data || !$this->sensitive_fields_mask) {
            return;
        }

        $fieldsToMask = $this->sensitive_fields_mask;

        if ($this->old_values) {
            foreach ($fieldsToMask as $field) {
                if (isset($this->old_values[$field])) {
                    $this->old_values[$field] = '***MASKED***';
                }
            }
        }

        if ($this->new_values) {
            foreach ($fieldsToMask as $field) {
                if (isset($this->new_values[$field])) {
                    $this->new_values[$field] = '***MASKED***';
                }
            }
        }

        $this->save();
    }

    /**
     * Archivar registro antiguo para cumplimiento de retención
     */
    public function archive(): void
    {
        $this->update([
            'is_archived' => true,
            'archived_at' => Carbon::now(),
        ]);
    }

    /**
     * Obtener registros que expiraron y necesitan archivarse
     */
    public static function getExpiredRecords()
    {
        return static::expiredRetention()->get();
    }

    /**
     * Formatear para respuesta de API
     */
    public function toApiResponse(): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'model' => [
                'type' => class_basename($this->auditable_type),
                'id' => $this->auditable_id,
            ],
            'user' => [
                'id' => $this->user_id,
                'email' => $this->user_email,
                'name' => $this->user_name,
            ],
            'changes' => $this->getReadableChanges(),
            'context' => [
                'ip' => $this->ip_address,
                'method' => $this->request_method,
                'path' => $this->request_path,
            ],
            'sensitive' => $this->involves_sensitive_data,
            'reason' => $this->change_reason,
            'timestamp' => $this->created_at->toIso8601String(),
        ];
    }
}

