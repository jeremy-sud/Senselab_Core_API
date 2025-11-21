<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'detalle_ventas';

    /**
     * Atributos asignables.
     *
     * @var array<int,string>
     */
    protected $fillable = [
        'venta_id',
        'producto_id',
        'numero_linea',
        'cantidad',
        'precio_unitario',
        'subtotal_linea',
        'porcentaje_descuento',
        'monto_descuento',
        'subtotal_con_descuento',
        'tipo_impuesto_id',
        'tasa_impuesto',
        'monto_impuesto',
        'total_linea',
        'detalle_adicional',
        'activo',
        'eliminado',
    ];

    /**
     * Casts.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal_linea' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'monto_descuento' => 'decimal:2',
        'subtotal_con_descuento' => 'decimal:2',
        'tasa_impuesto' => 'decimal:2',
        'monto_impuesto' => 'decimal:2',
        'total_linea' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    /**
     * Atributos ocultos.
     *
     * @var array<int,string>
     */
    protected $hidden = [
        'eliminado',
    ];

    /**
     * Reglas de validación (referenciales, para uso externo).
     *
     * @var array<string,string>
     */
    public static $rules = [
        'venta_id' => 'required|exists:ventas,id',
        'producto_id' => 'required|exists:productos,id',
        'numero_linea' => 'required|integer|min:1',
        'cantidad' => 'required|numeric|min:0.01',
        'precio_unitario' => 'required|numeric|min:0',
        'subtotal_linea' => 'required|numeric|min:0',
        'porcentaje_descuento' => 'nullable|numeric|min:0|max:100',
        'monto_descuento' => 'nullable|numeric|min:0',
        'subtotal_con_descuento' => 'required|numeric|min:0',
        'tipo_impuesto_id' => 'nullable|exists:tipos_impuesto,id',
        'tasa_impuesto' => 'nullable|numeric|min:0',
        'monto_impuesto' => 'nullable|numeric|min:0',
        'total_linea' => 'required|numeric|min:0',
        'detalle_adicional' => 'nullable|string',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Relaciones
     */
    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function tipoImpuesto()
    {
        return $this->belongsTo(TipoImpuesto::class, 'tipo_impuesto_id');
    }

    /**
     * Scopes útiles.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorProducto($query, $productoId)
    {
        return $query->where('producto_id', $productoId);
    }

    /**
     * Boot model: calcular subtotales, descuentos e impuesto antes de guardar.
     */
    protected static function boot()
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

            // Calcular monto_descuento (puede venir del % o directamente)
            $porcentajeDesc = $model->porcentaje_descuento ?? 0;
            $model->monto_descuento = round(($model->subtotal_linea * ($porcentajeDesc / 100)), 2);

            // Calcular subtotal_con_descuento
            $model->subtotal_con_descuento = round(max(0, $model->subtotal_linea - $model->monto_descuento), 2);

            // Calcular monto_impuesto si tiene tasa_impuesto
            $tasaImpuesto = $model->tasa_impuesto ?? 0;
            $model->monto_impuesto = round(($model->subtotal_con_descuento * ($tasaImpuesto / 100)), 2);

            // Calcular total_linea (subtotal con descuento + impuesto)
            $model->total_linea = round($model->subtotal_con_descuento + $model->monto_impuesto, 2);

            // Asegurar que los campos numéricos no sean nulos
            $model->porcentaje_descuento = $model->porcentaje_descuento ?? 0.00;
            $model->monto_descuento = $model->monto_descuento ?? 0.00;
            $model->tasa_impuesto = $model->tasa_impuesto ?? 0.00;
            $model->monto_impuesto = $model->monto_impuesto ?? 0.00;
        });
    }
}
