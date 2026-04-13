<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Modelo DataRetentionPolicy - Políticas de Retención de Datos
 *
 * Define políticas de retención automática para cumplimiento regulatorio.
 * Implementa anonimización, eliminación o archivado automático después de período.
 *
 * @package App\Models
 * @version 1.0.0 - FASE 3 Compliance
 */
class DataRetentionPolicy extends Model
{
    protected $table = 'data_retention_policies';

    protected $fillable = [
        'name',
        'description',
        'table_name',
        'columns',
        'conditions',
        'retention_days',
        'retention_period',
        'action_on_expiry',
        'archive_location',
        'anonymize_columns',
        'anonymize_strategy',
        'enabled',
        'auto_execute',
        'cron_expression',
        'last_execution_at',
        'rows_affected',
        'last_error',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'columns' => 'array',
        'conditions' => 'array',
        'anonymize_columns' => 'array',
        'metadata' => 'array',
        'enabled' => 'boolean',
        'auto_execute' => 'boolean',
        'last_execution_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ════════════════════════════════════════════════════════════════
    // RELACIONES
    // ════════════════════════════════════════════════════════════════

    /**
     * Usuario que creó la política
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'created_by');
    }

    // ════════════════════════════════════════════════════════════════
    // SCOPES
    // ════════════════════════════════════════════════════════════════

    /**
     * Scope: Políticas activas
     */
    public function scopeActive(Builder $query): Builder{
        return $query->where('enabled', true);
    }

    /**
     * Scope: Políticas con ejecución automática
     */
    public function scopeAutoExecute(Builder $query): Builder{
        return $query->where('auto_execute', true)
                     ->where('enabled', true);
    }

    /**
     * Scope: Por nombre de tabla
     */
    public function scopeForTable(Builder $query, string $table): Builder{
        return $query->where('table_name', $table);
    }

    /**
     * Scope: Políticas que no se han ejecutado
     */
    public function scopeNeverExecuted(Builder $query): Builder{
        return $query->whereNull('last_execution_at');
    }

    /**
     * Scope: Políticas con errores
     */
    public function scopeWithErrors(Builder $query): Builder{
        return $query->whereNotNull('last_error');
    }

    // ════════════════════════════════════════════════════════════════
    // MÉTODOS DE NEGOCIO
    // ════════════════════════════════════════════════════════════════

    /**
     * Operadores SQL permitidos para condiciones de retención.
     */
    private const ALLOWED_OPERATORS = ['=', '!=', '<', '>', '<=', '>=', '<>', 'like', 'not like'];

    /**
     * Obtener cantidad de registros que cumplen condiciones.
     *
     * Usa query builder con parameter binding para prevenir SQL injection.
     */
    public function getAffectedRecordsCount(): int
    {
        try {
            $query = \DB::table($this->table_name);

            if ($this->conditions) {
                foreach ($this->conditions as $condition) {
                    $operator = strtolower(trim($condition['operator'] ?? '='));
                    if (!in_array($operator, self::ALLOWED_OPERATORS, true)) {
                        return 0;
                    }
                    $query->where(
                        $condition['field'],
                        $operator,
                        $condition['value']
                    );
                }
            }

            $retentionDate = Carbon::now()->subDays($this->retention_days)->toDateString();
            $query->where('created_at', '<=', $retentionDate);

            return $query->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Ejecutar política de retención
     */
    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function execute(): array
    {
        if (!$this->enabled) {
            return ['success' => false, 'message' => 'Política deshabilitada'];
        }

        try {
            $affectedCount = $this->getAffectedRecordsCount();

            if ($affectedCount === 0) {
                return ['success' => true, 'affected' => 0, 'message' => 'Sin registros que procesar'];
            }

            // Ejecutar acción según configuración
            $result = match ($this->action_on_expiry) {
                'hard_delete' => $this->executeHardDelete(),
                'soft_delete' => $this->executeSoftDelete(),
                'archive' => $this->executeArchive(),
                'anonymize' => $this->executeAnonymize(),
                default => ['success' => false, 'message' => 'Acción desconocida'],
            };

            if ($result['success']) {
                $this->recordSuccessfulExecution($affectedCount);
            } else {
                $this->recordFailedExecution($result['error'] ?? 'Error desconocido');
            }

            return $result;
        } catch (\Exception $e) {
            $this->recordFailedExecution($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Ejecutar eliminación permanente
     */
    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    protected function executeHardDelete(): array
    {
        try {
            $query = \DB::table($this->table_name);

            if ($this->conditions) {
                foreach ($this->conditions as $condition) {
                    $query->where(
                        $condition['field'],
                        $condition['operator'],
                        $condition['value']
                    );
                }
            }

            $retentionDate = Carbon::now()->subDays($this->retention_days)->toDateString();
            $query->where('created_at', '<=', $retentionDate);

            $affected = $query->delete();

            return [
                'success' => true,
                'action' => 'hard_delete',
                'affected' => $affected,
                'message' => "Eliminados {$affected} registros de {$this->table_name}",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Ejecutar soft delete (si existe)
     */
    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    protected function executeSoftDelete(): array
    {
        try {
            $query = \DB::table($this->table_name);

            if ($this->conditions) {
                foreach ($this->conditions as $condition) {
                    $query->where(
                        $condition['field'],
                        $condition['operator'],
                        $condition['value']
                    );
                }
            }

            $retentionDate = Carbon::now()->subDays($this->retention_days)->toDateString();
            $query->where('created_at', '<=', $retentionDate)
                  ->whereNull('deleted_at');

            $affected = $query->update(['deleted_at' => Carbon::now()]);

            return [
                'success' => true,
                'action' => 'soft_delete',
                'affected' => $affected,
                'message' => "Soft-deleted {$affected} registros de {$this->table_name}",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Ejecutar archivado
     */
    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    protected function executeArchive(): array
    {
        // Implementar archivado a otro servidor/storage
        return [
            'success' => true,
            'action' => 'archive',
            'affected' => 0,
            'message' => 'Archivado pendiente de implementación',
        ];
    }

    /**
     * Ejecutar anonimización
     */
    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    protected function executeAnonymize(): array
    {
        try {
            if (!$this->anonymize_columns) {
                return ['success' => false, 'error' => 'Sin columnas de anonimización configuradas'];
            }

            $query = \DB::table($this->table_name);

            if ($this->conditions) {
                foreach ($this->conditions as $condition) {
                    $query->where(
                        $condition['field'],
                        $condition['operator'],
                        $condition['value']
                    );
                }
            }

            $retentionDate = Carbon::now()->subDays($this->retention_days)->toDateString();
            $query->where('created_at', '<=', $retentionDate);

            $updates = [];
            foreach ($this->anonymize_columns as $column) {
                $updates[$column] = \DB::raw("CONCAT('anon_', SUBSTR(MD5(id), 1, 8))");
            }

            $affected = $query->update($updates);

            return [
                'success' => true,
                'action' => 'anonymize',
                'affected' => $affected,
                'message' => "Anonimizados {$affected} registros de {$this->table_name}",
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Registrar ejecución exitosa
     */
    protected function recordSuccessfulExecution(int $affectedRows): void
    {
        $this->update([
            'last_execution_at' => Carbon::now(),
            'rows_affected' => $affectedRows,
            'last_error' => null,
        ]);

        // Crear registro de auditoría
        \Log::info("Política de retención '{$this->name}' ejecutada: {$affectedRows} registros procesados");
    }

    /**
     * Registrar ejecución fallida
     */
    protected function recordFailedExecution(string $error): void
    {
        $this->update([
            'last_execution_at' => Carbon::now(),
            'last_error' => $error,
        ]);

        \Log::error("Política de retención '{$this->name}' falló: {$error}");
    }

    /**
     * Obtener configuración JSON de La política
     */
    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function getConfigurationJson(): array
    {
        return [
            'name' => $this->name,
            'table' => $this->table_name,
            'retention' => [
                'days' => $this->retention_days,
                'period' => $this->retention_period,
            ],
            'action' => [
                'type' => $this->action_on_expiry,
                'archive_location' => $this->archive_location,
                'anonymize_columns' => $this->anonymize_columns,
            ],
            'execution' => [
                'auto' => $this->auto_execute,
                'cron' => $this->cron_expression,
                'last_run' => $this->last_execution_at?->toIso8601String(),
                'last_rows' => $this->rows_affected,
            ],
        ];
    }
}
