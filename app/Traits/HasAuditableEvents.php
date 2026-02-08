<?php

namespace App\Traits;

use App\Services\AuditService;

/**
 * Trait HasAuditableEvents
 *
 * Proporciona auditoría automática de cambios en modelos Eloquent.
 * Registra automáticamente creaciones, actualizaciones y eliminaciones.
 *
 * @package App\Traits
 * @version 1.0.0
 *
 * Use en modelos:
 * ```php
 * class Usuario extends Model {
 *     use HasAuditableEvents;
 * }
 * ```
 */
trait HasAuditableEvents
{
    /**
     * Boot the trait
     */
    public static function bootHasAuditableEvents(): void
    {
        // Registrar evento al crear
        static::created(function ($model) {
            AuditService::record(
                'created',
                $model,
                null,
                $model->getAttributes()
            );
        });

        // Registrar evento al actualizar
        static::updated(function ($model) {
            AuditService::record(
                'updated',
                $model,
                $model->getOriginal(),
                $model->getChanges()
            );
        });

        // Registrar evento al eliminar
        static::deleted(function ($model) {
            AuditService::record(
                'deleted',
                $model,
                $model->getAttributes(),
                null
            );
        });

        // Registrar evento al restaurar (soft delete)
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                AuditService::record(
                    'restored',
                    $model,
                    null,
                    $model->getAttributes()
                );
            });
        }
    }

    /**
     * Obtener la auditoría de este modelo
     *
     * @param string|null $event Filtrar por evento específico
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAuditLog(?string $event = null)
    {
        return AuditService::getModelAudit(static::class, $this->getKey(), $event);
    }

    /**
     * Obtener solo creación de este modelo
     */
    public function getAuditCreation()
    {
        return $this->getAuditLog('created')->first();
    }

    /**
     * Obtener todas las actualizaciones de este modelo
     */
    public function getAuditUpdates()
    {
        return $this->getAuditLog('updated');
    }

    /**
     * Obtener eliminació nes de este modelo
     */
    public function getAuditDeletion()
    {
        return $this->getAuditLog('deleted')->first();
    }

    /**
     * Obtener quién creó este registro
     *
     * @return \App\Models\Usuario|null
     */
    public function getAuditCreatedBy()
    {
        $audit = $this->getAuditCreation();

        if ($audit && $audit->user_id) {
            return \App\Models\Usuario::find($audit->user_id);
        }

        return null;
    }

    /**
     * Obtener cuándo fue creado este registro (desde auditoría)
     */
    public function getAuditCreatedAt()
    {
        return $this->getAuditCreation()?->created_at;
    }

    /**
     * Obtener
 el Historial de cambios de este modelo
     */
    public function getChangeHistory()
    {
        return $this->getAuditLog('updated')
            ->map(function ($audit) {
                return [
                    'timestamp' => $audit->created_at,
                    'user' => $audit->user_name ?? $audit->user_email,
                    'changes' => $audit->field_changes,
                ];
            })
            ->toArray();
    }

    /**
     * ¿Ha sido actualizado desde su creación?
     */
    public function hasBeenUpdated(): bool
    {
        return $this->getAuditUpdates()->count() > 0;
    }

    /**
     * ¿Cuántas veces ha sido actualizado?
     */
    public function getUpdateCount(): int
    {
        return $this->getAuditUpdates()->count();
    }

    /**
     * Obtener la última actualización
     */
    public function getLastUpdate()
    {
        return $this->getAuditUpdates()->sortByDesc('created_at')->first();
    }

    /**
     * ¿Quién fue el último en actualizar?
     */
    public function getLastUpdatedBy()
    {
        $audit = $this->getLastUpdate();

        if ($audit && $audit->user_id) {
            return \App\Models\Usuario::find($audit->user_id);
        }

        return null;
    }

    /**
     * ¿Cuándo fue la última actualización?
     */
    public function getLastUpdatedAt()
    {
        return $this->getLastUpdate()?->created_at;
    }

    /**
     * Exportar historial de auditoría
     */
    public function exportAuditLog(string $format = 'json')
    {
        $logs = $this->getAuditLog()->map->toApiResponse();

        return match($format) {
            'json' => json_encode($logs),
            'array' => $logs->toArray(),
            'csv' => $this->convertToCsv($logs),
            default => $logs,
        };
    }

    /**
     * Convertir a CSV
     */
    protected function convertToCsv($logs): string
    {
        $csv = "Event,ModelType,ModelId,UserId,UserEmail,Timestamp,ChangedFields\n";

        foreach ($logs as $log) {
            $changedFields = implode('|', $log['changed_fields'] ?? []);
            $csv .= "{$log['event']},{$log['model_type']},{$log['model_id']},{$log['user']['id']},{$log['user']['email']},{$log['created_at']},{$changedFields}\n";
        }

        return $csv;
    }
}
