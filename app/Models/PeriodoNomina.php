<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `periodos_nomina`.
 * Generado a partir del SHOW CREATE TABLE obtenido.
 */
class PeriodoNomina extends Model
{
    use HasFactory, BelongsToTenant;

    protected $table = 'periodos_nomina';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'nombre_periodo',
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago_estimada',
        'estado',
        'observaciones',
        'activo',
        'eliminado',
        'creado_en',
        'actualizado_en',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_pago_estimada' => 'date',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'nombre_periodo' => 'required|string',
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
    ];

    /* --------------------- Relaciones --------------------- */
    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function pagosNomina()
    {
        return $this->hasMany(PagoNomina::class, 'periodo_nomina_id');
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

    public function scopeAbiertos($q)
    {
        return $q->where('estado', 'Abierto');
    }

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot()
    {
        parent::boot();

        static::saving(function (PeriodoNomina $p) {
            // Normalizar nombre y estado
            if (isset($p->nombre_periodo)) {
                $p->nombre_periodo = trim($p->nombre_periodo);
            }

            if (isset($p->estado)) {
                $p->estado = Str::ucfirst(Str::lower(trim($p->estado)));
            }

            // Coherencia de fechas
            if (isset($p->fecha_inicio, $p->fecha_fin)) {
                if ($p->fecha_fin < $p->fecha_inicio) {
                    throw new \InvalidArgumentException('fecha_fin debe ser mayor o igual a fecha_inicio');
                }
            }
        });
    }

    /* --------------------- Helpers --------------------- */
    public function duracionDias()
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return null;
        }

        return $this->fecha_fin->diffInDays($this->fecha_inicio) + 1;
    }
}
