<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class DetalleSalidaInventario extends Model
{
    use HasCustomSoftDeletes, HasAuditFields, HasActiveScope;
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
        'numero_linea',
        'cantidad',
        'costo_unitario',
        'total_linea',
        'lote',
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
        'total_linea' => 'decimal:2',
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
        'numero_linea' => 'required|integer|min:1',
        'cantidad' => 'required|numeric|min:0.01',
        'costo_unitario' => 'required|numeric|min:0',
        'total_linea' => 'required|numeric|min:0',
        'lote' => 'nullable|string|max:100',
        'observaciones' => 'nullable|string',
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
     * Boot the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Validaciones
            if ($model->cantidad <= 0) {
                throw new \Exception('La cantidad debe ser mayor que cero.');
            }
            
            if ($model->costo_unitario < 0) {
                throw new \Exception('El costo unitario no puede ser negativo.');
            }

            // Calcular total_linea (cantidad * costo_unitario)
            if ($model->isDirty(['cantidad', 'costo_unitario']) || !$model->total_linea) {
                $model->total_linea = round($model->cantidad * $model->costo_unitario, 2);
            }
        });
    }
}