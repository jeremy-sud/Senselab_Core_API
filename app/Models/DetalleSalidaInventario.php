<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleSalidaInventario extends Model
{
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'detalle_salidas_inventario';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'salida_inventario_id',
        'producto_id',
        'cantidad',
        'costo_unitario_salida',
        'subtotal',
        'lote',
        'fecha_vencimiento',
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
        'costo_unitario_salida' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'fecha_vencimiento' => 'date',
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
        'salida_inventario_id' => 'required|exists:salidas_inventario,id',
        'producto_id' => 'required|exists:productos,id',
        'cantidad' => 'required|numeric|min:0.01',
        'costo_unitario_salida' => 'required|numeric|min:0',
        'subtotal' => 'required|numeric|min:0',
        'lote' => 'nullable|string|max:50',
        'fecha_vencimiento' => 'nullable|date',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Get the salida de inventario that owns the detalle.
     */
    public function salidaInventario()
    {
        return $this->belongsTo(SalidaInventario::class);
    }

    /**
     * Get the producto associated with the detalle.
     */
    public function producto()
    {
        return $this->belongsTo(Producto::class);
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
     * Scope para filtrar por lote.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $lote
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorLote($query, $lote)
    {
        return $query->where('lote', $lote);
    }

    /**
     * Scope para filtrar productos por fecha de vencimiento.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \DateTime|string  $fecha
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePorFechaVencimiento($query, $fecha)
    {
        return $query->whereDate('fecha_vencimiento', '=', $fecha);
    }

    /**
     * Scope para filtrar productos vencidos a una fecha específica.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  \DateTime|string|null  $fecha
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVencidosA($query, $fecha = null)
    {
        $fecha = $fecha ?: now();
        return $query->whereNotNull('fecha_vencimiento')
                    ->whereDate('fecha_vencimiento', '<', $fecha);
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
            // Calcular el subtotal si no está establecido o si cambiaron cantidad o costo
            if ($model->isDirty(['cantidad', 'costo_unitario_salida']) || !$model->subtotal) {
                $model->subtotal = $model->cantidad * $model->costo_unitario_salida;
            }

            // Validar que la cantidad y costo sean positivos
            if ($model->cantidad <= 0) {
                throw new \Exception('La cantidad debe ser mayor que cero.');
            }
            
            if ($model->costo_unitario_salida < 0) {
                throw new \Exception('El costo unitario no puede ser negativo.');
            }
        });
    }
}