<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class InventarioProducto extends Model
{
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'inventario_productos';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'almacen_id',
        'producto_id',
        'stock_actual',
        'costo_promedio',
        'stock_minimo',
        'stock_maximo',
        'ubicacion',
        'activo',
        'eliminado'
    ];

    protected $casts = [
        'stock_actual' => 'decimal:2',
        'costo_promedio' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'stock_maximo' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorAlmacen($query, $almacenId)
    {
        return $query->where('almacen_id', $almacenId);
    }

    public function scopeBajoStockMinimo($query)
    {
        return $query->whereRaw('stock_actual <= stock_minimo');
    }

    public function scopeSobreStockMaximo($query)
    {
        return $query->whereRaw('stock_actual >= stock_maximo');
    }

    public function necesitaReposicion(): bool
    {
        return $this->stock_actual <= $this->stock_minimo;
    }

    public function tieneExceso(): bool
    {
        return $this->stock_actual >= $this->stock_maximo;
    }
}
