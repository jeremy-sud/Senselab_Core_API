<?php

namespace App\Traits;

use App\Models\AuditoriaActividad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Trait HasAuditFields
 *
 * Registra automáticamente quién creó, actualizó o eliminó un registro.
 * También crea entradas en la tabla auditoria_actividades para trazabilidad completa.
 *
 * Uso:
 * - El trait busca campos created_by, updated_by, deleted_by si existen
 * - Si no existen, solo registra en auditoria_actividades
 * - Siempre registra IP, user agent y datos antes/después
 *
 * @package App\Traits
 */
trait HasAuditFields
{
    /**
     * Boot the audit fields trait for a model.
     */
    protected static function bootHasAuditFields(): void
    {
        // Al crear un registro
        static::creating(function ($model) {
            if (Auth::check()) {
                // Solo asignar si la columna existe
                if ($model->hasColumn('created_by')) {
                    $model->created_by = Auth::id();
                }
                if ($model->hasColumn('updated_by')) {
                    $model->updated_by = Auth::id();
                }
            }
        });

        // Al actualizar un registro
        static::updating(function ($model) {
            if (Auth::check() && $model->hasColumn('updated_by')) {
                $model->updated_by = Auth::id();
            }
        });

        // Al eliminar un registro (soft delete)
        static::deleting(function ($model) {
            if (Auth::check() && $model->hasColumn('deleted_by')) {
                // Para soft deletes custom
                if (method_exists($model, 'trashed') && !$model->isForceDeleting()) {
                    $model->deleted_by = Auth::id();
                }
            }
        });

        // Después de crear - registrar en auditoría
        static::created(function ($model) {
            $model->registrarAuditoria('crear', null, $model->getAttributes());
        });

        // Después de actualizar - registrar cambios en auditoría
        static::updated(function ($model) {
            $cambios = $model->getDirty();
            if (!empty($cambios)) {
                $model->registrarAuditoria('actualizar', $model->getOriginal(), $cambios);
            }
        });

        // Después de eliminar - registrar en auditoría
        static::deleted(function ($model) {
            $model->registrarAuditoria('eliminar', $model->getAttributes(), null);
        });

        // Después de restaurar - registrar en auditoría
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->registrarAuditoria('restaurar', ['eliminado' => true], ['eliminado' => false]);
            });
        }
    }

    /**
     * Verifica si la tabla tiene una columna específica.
     *
     * @param string $column
     * @return bool
     */
    protected function hasColumn(string $column): bool
    {
        return in_array($column, $this->getFillable()) || 
               in_array($column, array_keys($this->getAttributes()));
    }

    /**
     * Registra la actividad en la tabla de auditoría.
     *
     * @param string $accion
     * @param array|null $datosAnteriores
     * @param array|null $datosNuevos
     * @return void
     */
    protected function registrarAuditoria(string $accion, ?array $datosAnteriores, ?array $datosNuevos): void
    {
        try {
            // Solo registrar si hay un usuario autenticado y una empresa
            if (!Auth::check()) {
                return;
            }

            $usuario = Auth::user();
            
            // Obtener empresa_id del modelo o del usuario
            $empresaId = $this->empresa_id ?? $usuario->empresa_id ?? null;

            if (!$empresaId) {
                return;
            }

            // Filtrar campos sensibles de los datos
            $camposSensibles = ['password_hash', 'pin_llave_fe_hash', 'certificado_llave_fe'];
            
            if ($datosAnteriores) {
                $datosAnteriores = array_diff_key($datosAnteriores, array_flip($camposSensibles));
            }
            
            if ($datosNuevos) {
                $datosNuevos = array_diff_key($datosNuevos, array_flip($camposSensibles));
            }

            // Crear registro de auditoría
            AuditoriaActividad::create([
                'usuario_id' => $usuario->id,
                'empresa_id' => $empresaId,
                'accion' => $accion,
                'tabla' => $this->getTable(),
                'registro_id' => $this->getKey(),
                'datos_anteriores' => $datosAnteriores ? json_encode($datosAnteriores) : null,
                'datos_nuevos' => $datosNuevos ? json_encode($datosNuevos) : null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        } catch (\Exception $e) {
            // Log el error pero no interrumpir el flujo normal
            Log::error('Error al registrar auditoría: ' . $e->getMessage(), [
                'model' => get_class($this),
                'action' => $accion,
            ]);
        }
    }

    /**
     * Obtiene el historial de cambios de este registro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function historialAuditoria()
    {
        return AuditoriaActividad::where('tabla', $this->getTable())
            ->where('registro_id', $this->getKey())
            ->orderBy('creado_en', 'desc')
            ->get();
    }

    /**
     * Obtiene el usuario que creó el registro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function creador()
    {
        if ($this->hasColumn('created_by')) {
            return $this->belongsTo(\App\Models\Usuario::class, 'created_by');
        }
        return null;
    }

    /**
     * Obtiene el usuario que actualizó el registro por última vez.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function actualizador()
    {
        if ($this->hasColumn('updated_by')) {
            return $this->belongsTo(\App\Models\Usuario::class, 'updated_by');
        }
        return null;
    }

    /**
     * Obtiene el usuario que eliminó el registro.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo|null
     */
    public function eliminador()
    {
        if ($this->hasColumn('deleted_by')) {
            return $this->belongsTo(\App\Models\Usuario::class, 'deleted_by');
        }
        return null;
    }
}
