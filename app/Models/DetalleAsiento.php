<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class DetalleAsiento extends Model
{
    use HasCustomSoftDeletes, HasAuditFields, HasActiveScope;
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'detalle_asientos';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'asiento_contable_id',
        'cuenta_contable_id',
        'debe',
        'haber',
        'descripcion',
        'activo',
        'eliminado',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'debe' => 'decimal:2',
        'haber' => 'decimal:2',
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
     * Las reglas de validación para el modelo.
     *
     * @var array<string, string>
     */
    public static $rules = [
        'asiento_contable_id' => 'required|exists:asientos_contables,id',
        'cuenta_contable_id' => 'required|exists:cuentas_contables,id',
        'debe' => 'required|numeric|min:0',
        'haber' => 'required|numeric|min:0',
        'descripcion' => 'nullable|string',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Get the asiento contable that owns the detalle.
     */
    public function asientoContable(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AsientoContable::class);
    }

    /**
     * Get the cuenta contable associated with the detalle.
     */
    public function cuentaContable(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CuentaContable::class);
    }

    /**
     * Scope para filtrar detalles activos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Verifica si el detalle está balanceado (debe = haber).
     *
     * @return bool
     */
    public function estaBalanceado()
    {
        return $this->debe === $this->haber;
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
            // Validación de que debe o haber debe ser cero, pero no ambos
            if ($model->debe > 0 && $model->haber > 0) {
                throw new \Exception('Un detalle de asiento no puede tener valores en debe y haber simultáneamente.');
            }
            
            if ($model->debe == 0 && $model->haber == 0) {
                throw new \Exception('Un detalle de asiento debe tener un valor ya sea en debe o en haber.');
            }
        });
    }
}