<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleEntradaInventario extends Model
{
    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'detalle_entradas_inventario';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'entrada_inventario_id',
        'producto_id',
        'numero_linea',
        'cantidad',
        'costo_unitario',
        'subtotal',
        'porcentaje_impuesto',
        'monto_impuesto',
        'total_linea',
        'lote',
        'fecha_vencimiento',
        'observaciones',
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
        'costo_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'porcentaje_impuesto' => 'decimal:2',
        'monto_impuesto' => 'decimal:2',
        'total_linea' => 'decimal:2',
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
        'entrada_inventario_id' => 'required|exists:entradas_inventario,id',
        'producto_id' => 'required|exists:productos,id',
        'numero_linea' => 'required|integer|min:1',
        'cantidad' => 'required|numeric|min:0.01',
        'costo_unitario' => 'required|numeric|min:0',
        'subtotal' => 'required|numeric|min:0',
        'porcentaje_impuesto' => 'nullable|numeric|min:0|max:100',
        'monto_impuesto' => 'nullable|numeric|min:0',
        'total_linea' => 'required|numeric|min:0',
        'lote' => 'nullable|string|max:100',
        'fecha_vencimiento' => 'nullable|date',
        'observaciones' => 'nullable|string',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**
     * Get the entrada de inventario that owns the detalle.
     */
    public function entradaInventario()
    {
        return $this->belongsTo(EntradaInventario::class);
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
     * Scope para filtrar productos próximos a vencer.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $dias
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProximosAVencer($query, $dias = 30)
    {
        return $query->whereNotNull('fecha_vencimiento')
                    ->whereDate('fecha_vencimiento', '>=', now())
                    ->whereDate('fecha_vencimiento', '<=', now()->addDays($dias));
    }

    /**
     * Scope para filtrar productos vencidos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVencidos($query)
    {
        return $query->whereNotNull('fecha_vencimiento')
                    ->whereDate('fecha_vencimiento', '<', now());
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
            // Calcular subtotal (cantidad * costo_unitario)
            if ($model->isDirty(['cantidad', 'costo_unitario']) || !$model->subtotal) {
                $model->subtotal = round($model->cantidad * $model->costo_unitario, 2);
            }

            // Calcular monto_impuesto si tiene porcentaje_impuesto
            $porcentaje = $model->porcentaje_impuesto ?? 0;
            $model->monto_impuesto = round($model->subtotal * ($porcentaje / 100), 2);

            // Calcular total_linea (subtotal + impuesto)
            $model->total_linea = round($model->subtotal + $model->monto_impuesto, 2);

            // Asegurar valores por defecto
            $model->porcentaje_impuesto = $model->porcentaje_impuesto ?? 0.00;
            $model->monto_impuesto = $model->monto_impuesto ?? 0.00;
        });
    }
}