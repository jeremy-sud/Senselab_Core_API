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
        'cantidad',
        'costo_unitario',
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
        'costo_unitario' => 'decimal:2',
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
        'entrada_inventario_id' => 'required|exists:entradas_inventario,id',
        'producto_id' => 'required|exists:productos,id',
        'cantidad' => 'required|numeric|min:0.01',
        'costo_unitario' => 'required|numeric|min:0',
        'subtotal' => 'required|numeric|min:0',
        'lote' => 'nullable|string|max:50',
        'fecha_vencimiento' => 'nullable|date',
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
            // Calcular el subtotal si no está establecido
            if ($model->isDirty(['cantidad', 'costo_unitario']) || !$model->subtotal) {
                $model->subtotal = $model->cantidad * $model->costo_unitario;
            }
        });
    }
}