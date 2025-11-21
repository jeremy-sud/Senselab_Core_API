<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditoriaActividad extends Model
{
    use HasFactory;

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
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    /**
     * Relación con la empresa
     */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Scope para filtrar por acción
     */
    public function scopeAccion($query, $accion)
    {
        return $query->where('accion', $accion);
    }

    /**
     * Scope para filtrar por tabla
     */
    public function scopeTabla($query, $tabla)
    {
        return $query->where('tabla', $tabla);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /**
     * Scope para filtrar por empresa
     */
    public function scopePorEmpresa($query, $empresaId)
    {
        return $query->where('empresa_id', $empresaId);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('creado_en', [$fechaInicio, $fechaFin]);
    }

    /**
     * Registrar una actividad de auditoría
     */
    public static function registrar($accion, $tabla, $registroId = null, $datosAnteriores = null, $datosNuevos = null)
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
    public function getCambiosAttribute()
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
