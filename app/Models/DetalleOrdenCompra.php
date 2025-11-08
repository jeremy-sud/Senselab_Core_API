<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleOrdenCompra extends Model
{
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'detalle_ordenes_compra';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'orden_compra_id',
        'producto_id',
        'descripcion_producto',
        'unidad_medida_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'cantidad_recibida',
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
        'subtotal' => 'decimal:2',
        'cantidad_recibida' => 'decimal:2',
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
        'cantidad_pendiente',
        'esta_completado',
    ];

    /**
     * Las reglas de validación para el modelo.
     *
     * @var array<string, string>
     */
    public static $rules = [
        'orden_compra_id' => 'required|exists:ordenes_compra,id',
        'producto_id' => 'required|exists:productos,id',
        'descripcion_producto' => 'nullable|string',
        'unidad_medida_id' => 'nullable|exists:unidades_medida,id',
        'cantidad' => 'required|numeric|min:0.01',
        'precio_unitario' => 'required|numeric|min:0',
        'subtotal' => 'required|numeric|min:0',
        'cantidad_recibida' => 'nullable|numeric|min:0',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Get the orden de compra that owns the detalle.
     */
    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    /**
     * Get the producto associated with the detalle.
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    /**
     * Get the unidad de medida associated with the detalle.
     */
    public function unidadMedida()
    {
        return $this->belongsTo(UnidadMedida::class);
    }

    /**
     * Get the cantidad pendiente de recibir.
     *
     * @return float
     */
    public function getCantidadPendienteAttribute()
    {
        return max(0, $this->cantidad - $this->cantidad_recibida);
    }

    /**
     * Determina si se ha recibido toda la cantidad ordenada.
     *
     * @return bool
     */
    public function getEstaCompletadoAttribute()
    {
        return $this->cantidad_recibida >= $this->cantidad;
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
     * Scope para filtrar detalles pendientes de recepción.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendientesRecepcion($query)
    {
        return $query->whereRaw('cantidad > cantidad_recibida');
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
            // Calcular el subtotal si no está establecido o si cambiaron cantidad o precio
            if ($model->isDirty(['cantidad', 'precio_unitario']) || !$model->subtotal) {
                $model->subtotal = $model->cantidad * $model->precio_unitario;
            }

            // Validar que la cantidad recibida no exceda la cantidad ordenada
            if ($model->cantidad_recibida > $model->cantidad) {
                throw new \Exception('La cantidad recibida no puede ser mayor que la cantidad ordenada.');
            }
        });
    }
}