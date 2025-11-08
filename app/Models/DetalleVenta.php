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
        'horario_ruta_id',
        'descripcion_producto',
        'unidad_medida_id',
        'cantidad',
        'precio_unitario',
        'descuento_porcentaje',
        'monto_descuento_input',
        'subtotal_bruto',
        'monto_descuento_calculado',
        'subtotal_neto',
        'codigo_impuesto_dgt',
        'tarifa_impuesto_porc',
        'monto_impuesto',
        'codigo_tarifa_dgt',
        'exoneracion_tipo_doc',
        'monto_exoneracion',
        'cantidad_entregada',
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
        'descuento_porcentaje' => 'decimal:2',
        'monto_descuento_input' => 'decimal:2',
        'subtotal_bruto' => 'decimal:2',
        'monto_descuento_calculado' => 'decimal:2',
        'subtotal_neto' => 'decimal:2',
        'tarifa_impuesto_porc' => 'decimal:2',
        'monto_impuesto' => 'decimal:2',
        'monto_exoneracion' => 'decimal:2',
        'cantidad_entregada' => 'decimal:2',
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
     * Atributos añadidos.
     *
     * @var array<int,string>
     */
    protected $appends = [
        'cantidad_pendiente',
    ];

    /**
     * Reglas de validación (referenciales, para uso externo).
     *
     * @var array<string,string>
     */
    public static $rules = [
        'venta_id' => 'required|exists:ventas,id',
        'producto_id' => 'required|exists:productos,id',
        'cantidad' => 'required|numeric|min:0.01',
        'precio_unitario' => 'required|numeric|min:0',
        'descuento_porcentaje' => 'nullable|numeric|min:0',
        'monto_descuento_input' => 'nullable|numeric|min:0',
        'tarifa_impuesto_porc' => 'nullable|numeric|min:0',
        'monto_exoneracion' => 'nullable|numeric|min:0',
        'cantidad_entregada' => 'nullable|numeric|min:0',
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

    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    public function horarioRuta()
    {
        return $this->belongsTo(HorarioRuta::class, 'horario_ruta_id');
    }

    /**
     * Cantidad pendiente por entregar.
     *
     * @return float
     */
    public function getCantidadPendienteAttribute()
    {
        return max(0, ($this->cantidad ?? 0) - ($this->cantidad_entregada ?? 0));
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

    public function scopePendientesEntrega($query)
    {
        return $query->whereRaw('cantidad > cantidad_entregada');
    }

    public function scopeConImpuesto($query)
    {
        return $query->whereNotNull('codigo_impuesto_dgt')->where('tarifa_impuesto_porc', '>', 0);
    }

    /**
     * Boot model: calcular subtotales, descuentos e impuesto antes de guardar.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Valores mínimos
            if (($model->cantidad ?? 0) <= 0) {
                throw new \Exception('La cantidad debe ser mayor que cero.');
            }
            if (($model->precio_unitario ?? 0) < 0) {
                throw new \Exception('El precio unitario no puede ser negativo.');
            }

            // Calcular subtotal bruto
            $model->subtotal_bruto = round(($model->cantidad * $model->precio_unitario), 2);

            // Calcular descuento: si hay monto_descuento_input > 0 lo usamos; si no, calcular por porcentaje
            if (isset($model->monto_descuento_input) && $model->monto_descuento_input > 0) {
                $model->monto_descuento_calculado = round(min($model->monto_descuento_input, $model->subtotal_bruto), 2);
            } else {
                $porc = $model->descuento_porcentaje ?? 0;
                $model->monto_descuento_calculado = round(($model->subtotal_bruto * ($porc / 100)), 2);
            }

            // Subtotal neto
            $model->subtotal_neto = round(max(0, $model->subtotal_bruto - ($model->monto_descuento_calculado ?? 0)), 2);

            // Calcular impuesto si aplicable
            $tarifa = $model->tarifa_impuesto_porc ?? 0;
            $montoImpuesto = round($model->subtotal_neto * ($tarifa / 100), 2);

            // Aplicar exoneración si existe (se resta del impuesto calculado, no generar negativo)
            if (($model->monto_exoneracion ?? 0) > 0) {
                $montoImpuesto = max(0, $montoImpuesto - $model->monto_exoneracion);
            }

            $model->monto_impuesto = $montoImpuesto;

            // Asegurar que los campos numéricos no sean nulos
            $model->descuento_porcentaje = $model->descuento_porcentaje ?? 0.00;
            $model->monto_descuento_input = $model->monto_descuento_input ?? 0.00;
            $model->tarifa_impuesto_porc = $model->tarifa_impuesto_porc ?? 0.00;
            $model->monto_exoneracion = $model->monto_exoneracion ?? 0.00;
            $model->cantidad_entregada = $model->cantidad_entregada ?? 0.00;
        });
    }
}
