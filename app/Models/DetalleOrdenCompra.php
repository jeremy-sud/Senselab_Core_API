<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class DetalleOrdenCompra extends Model
{
    use HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    public $timestamps = true;
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'detalle_ordenes_compra';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var list<string>
     */
    protected $fillable = [
        'orden_compra_id',
        'producto_id',
        'numero_linea',
        'cantidad',
        'precio_unitario',
        'subtotal_linea',
        'porcentaje_impuesto',
        'monto_impuesto',
        'total_linea',
        'detalle_adicional',
        'activo',
        'eliminado',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal_linea' => 'decimal:2',
        'porcentaje_impuesto' => 'decimal:2',
        'monto_impuesto' => 'decimal:2',
        'total_linea' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Los atributos que deben ser ocultados para la serialización.
     *
     * @var list<string>
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
        'orden_compra_id' => 'required|exists:ordenes_compra,id',
        'producto_id' => 'required|exists:productos,id',
        'numero_linea' => 'required|integer|min:1',
        'cantidad' => 'required|numeric|min:0.01',
        'precio_unitario' => 'required|numeric|min:0',
        'subtotal_linea' => 'required|numeric|min:0',
        'porcentaje_impuesto' => 'nullable|numeric|min:0',
        'monto_impuesto' => 'nullable|numeric|min:0',
        'total_linea' => 'required|numeric|min:0',
        'detalle_adicional' => 'nullable|string',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Get the orden de compra that owns the detalle.
     */
    public function ordenCompra(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    /**
     * Get the producto associated with the detalle.
     */
    public function producto(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Producto::class);
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
     * Boot the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function ($model) {
            // Validaciones mínimas
            if (($model->cantidad ?? 0) <= 0) {
                throw new \Exception('La cantidad debe ser mayor que cero.');
            }
            if (($model->precio_unitario ?? 0) < 0) {
                throw new \Exception('El precio unitario no puede ser negativo.');
            }

            // Calcular subtotal_linea (cantidad * precio_unitario)
            $model->subtotal_linea = round(($model->cantidad * $model->precio_unitario), 2);

            // Calcular monto_impuesto si tiene porcentaje_impuesto
            $porcentajeImpuesto = $model->porcentaje_impuesto ?? 0;
            $model->monto_impuesto = round(($model->subtotal_linea * ($porcentajeImpuesto / 100)), 2);

            // Calcular total_linea (subtotal + impuesto)
            $model->total_linea = round($model->subtotal_linea + $model->monto_impuesto, 2);

            // Asegurar que los campos numéricos no sean nulos
            $model->porcentaje_impuesto = $model->porcentaje_impuesto ?? 0.00;
            $model->monto_impuesto = $model->monto_impuesto ?? 0.00;
        });
    }
}