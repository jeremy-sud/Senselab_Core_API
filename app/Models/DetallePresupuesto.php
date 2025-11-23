<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class DetallePresupuesto extends Model
{
    use HasCustomSoftDeletes, HasAuditFields, HasActiveScope;
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'detalle_presupuestos';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'presupuesto_id',
        'cuenta_contable_id',
        'monto_presupuestado',
        'activo',
        'eliminado',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monto_presupuestado' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Los atributos que deben ser ocultados para la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'eliminado',
    ];

    /**
     * Los atributos computados que deben ser agregados a los arrays.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'monto_ejecutado',
        'porcentaje_ejecucion',
    ];

    /**
     * Las reglas de validación para el modelo.
     *
     * @var array<string, string>
     */
    public static $rules = [
        'presupuesto_id' => 'required|exists:presupuestos,id',
        'cuenta_contable_id' => 'required|exists:cuentas_contables,id',
        'monto_presupuestado' => 'required|numeric|min:0',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Get the presupuesto that owns the detalle.
     */
    public function presupuesto()
    {
        return $this->belongsTo(Presupuesto::class);
    }

    /**
     * Get the cuenta contable associated with the detalle.
     */
    public function cuentaContable()
    {
        return $this->belongsTo(CuentaContable::class);
    }

    /**
     * Get the movimientos relacionados con este detalle de presupuesto.
     * 
     * TODO: Implementar modelo MovimientoPresupuesto
     */
    // public function movimientos()
    // {
    //     return $this->hasMany(MovimientoPresupuesto::class);
    // }

    /**
     * Get el monto ejecutado del presupuesto.
     *
     * @return float
     */
    public function getMontoEjecutadoAttribute()
    {
        // TODO: Descomentar cuando se implemente MovimientoPresupuesto
        // return $this->movimientos()
        //             ->where('activo', true)
        //             ->where('eliminado', false)
        //             ->sum('monto');
        return 0; // Temporal hasta implementar MovimientoPresupuesto
    }

    /**
     * Get el porcentaje de ejecución del presupuesto.
     *
     * @return float
     */
    public function getPorcentajeEjecucionAttribute()
    {
        if ($this->monto_presupuestado <= 0) {
            return 0;
        }
        return ($this->monto_ejecutado / $this->monto_presupuestado) * 100;
    }

    /**
     * Scope para filtrar detalles activos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para filtrar por exceso de presupuesto.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $porcentaje
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExcedidos($query, $porcentaje = 100)
    {
        return $query->whereHas('movimientos', function ($query) use ($porcentaje) {
            $query->selectRaw('SUM(monto) as total_ejecutado')
                  ->havingRaw('total_ejecutado > (detalle_presupuestos.monto_presupuestado * ?)', [$porcentaje / 100]);
        });
    }

    /**
     * Scope para filtrar por porcentaje de ejecución.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  float  $minimo
     * @param  float  $maximo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorPorcentajeEjecucion($query, $minimo = 0, $maximo = 100)
    {
        return $query->whereHas('movimientos', function ($query) use ($minimo, $maximo) {
            $query->selectRaw('SUM(monto) as total_ejecutado')
                  ->havingRaw('(total_ejecutado / detalle_presupuestos.monto_presupuestado * 100) BETWEEN ? AND ?', [$minimo, $maximo]);
        });
    }

    /**
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->monto_presupuestado < 0) {
                throw new \Exception('El monto presupuestado no puede ser negativo.');
            }
        });
    }
}