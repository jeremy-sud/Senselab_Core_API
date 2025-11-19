<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `presupuestos`.
 * Generado a partir del SHOW CREATE TABLE real.
 */
class Presupuesto extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'presupuestos';
    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'nombre',
        'periodo_inicio',
        'periodo_fin',
        'estado',
        'activo',
        'eliminado',
        'creado_en',
        'actualizado_en',
    ];

    protected $casts = [
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'nombre' => 'required|string',
        'periodo_inicio' => 'required|date',
        'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
        'estado' => 'required|string',
    ];

    /* --------------------- Relaciones --------------------- */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function detalles()
    {
        // Relación con detalle_presupuestos si existe
        return $this->hasMany(DetallePresupuesto::class, 'presupuesto_id');
    }

    /* --------------------- Scopes --------------------- */
    public function scopeActivos($q)
    {
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorEmpresa($q, $empresaId)
    {
        return $q->where('empresa_id', $empresaId);
    }

    public function scopePorEstado($q, $estado)
    {
        return $q->where('estado', $estado);
    }

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (Presupuesto $p) {
            if (isset($p->nombre)) {
                $p->nombre = trim($p->nombre);
            }
            if (isset($p->estado)) {
                $p->estado = Str::ucfirst(Str::lower(trim($p->estado)));
            }
            if (isset($p->periodo_inicio, $p->periodo_fin)) {
                if ($p->periodo_fin < $p->periodo_inicio) {
                    throw new \InvalidArgumentException('periodo_fin debe ser mayor o igual a periodo_inicio');
                }
            }
        });
    }

    /* --------------------- Helpers --------------------- */
    public function duracionDias()
    {
        if (!$this->periodo_inicio || !$this->periodo_fin) {
            return null;
        }
        return $this->periodo_fin->diffInDays($this->periodo_inicio) + 1;
    }
}
