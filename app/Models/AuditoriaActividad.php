<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
/** @use HasFactory<\Database\Factories\AuditoriaActividadFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditoriaActividad extends Model
{
    /** @use HasFactory<\Database\Factories\AuditoriaActividadFactory> */
    use HasFactory;
    use BelongsToTenant;

    protected $table = 'auditoria_actividades';

    public const CREATED_AT = 'creado_en';
    public const UPDATED_AT = null; // Solo tiene timestamp de creación

    protected $fillable = [
        'usuario_id',
        'empresa_id',
        'accion',
        'tabla',
        'registro_id',
        'datos_anteriores',
        'datos_nuevos',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'creado_en' => 'datetime',
    ];

    /**
     * Relación con el usuario que realizó la acción
     */
    public function usuario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con la empresa
     */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Scope para filtrar por acción
     */
    public function scopeAccion(Builder $query, mixed $accion): Builder{
        return $query->where('accion', $accion);
    }

    /**
     * Scope para filtrar por tabla
     */
    public function scopeTabla(Builder $query, mixed $tabla): Builder{
        return $query->where('tabla', $tabla);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopePorUsuario(Builder $query, mixed $usuarioId): Builder{
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para filtrar por empresa
     */
    public function scopePorEmpresa(Builder $query, mixed $empresaId): Builder{
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeEntreFechas(Builder $query, mixed $fechaInicio, mixed $fechaFin): Builder{
        return $query->whereBetween('creado_en', [$fechaInicio, $fechaFin]);
    }

    /**
     * Registrar una actividad de auditoría
     */
    public static function registrar(mixed $accion, mixed $tabla, mixed $registroId = null, mixed $datosAnteriores = null, mixed $datosNuevos = null): mixed
    {
        return self::create([
            'usuario_id' => auth()->id(),
            'empresa_id' => auth()->user()->empresa_id ?? 1,
            'accion' => $accion,
            'tabla' => $tabla,
            'registro_id' => $registroId,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Obtener cambios realizados (comparación de datos anteriores y nuevos)
     */
    public function getCambiosAttribute(): mixed
    {
        if (!$this->datos_anteriores || !$this->datos_nuevos) {
            return [];
        }

        $cambios = [];
        foreach ($this->datos_nuevos as $campo => $valorNuevo) {
            $valorAnterior = $this->datos_anteriores[$campo] ?? null;
            if ($valorAnterior !== $valorNuevo) {
                $cambios[$campo] = [
                    'anterior' => $valorAnterior,
                    'nuevo' => $valorNuevo,
                ];
            }
        }

        return $cambios;
    }
}
