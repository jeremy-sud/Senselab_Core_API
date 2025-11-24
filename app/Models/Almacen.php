<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Almacen extends Model
{
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    /**
     * Ubicaciones físicas donde se guarda el stock (bodegas, camiones, etc.).
     * Central para el control de stock.
     */
    protected $table = 'almacenes';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'empresa_id',
        'sucursal_id',
        'nombre',
        'codigo',
        'descripcion',
        'ubicacion',
        'responsable_id',
        'es_principal',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'es_principal' => 'boolean',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
     * Relación con la empresa propietaria del almacén.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    /**
     * Relación con la sucursal a la que pertenece el almacén.
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id')
                    ->withDefault(); // Permite que sea NULL
    }

    /**
     * Relación con el empleado responsable del almacén.
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'responsable_id')
                    ->withDefault(); // Permite que sea NULL
    }

    /**
     * Relación con las entradas de inventario.
     */
    public function entradasInventario(): HasMany
    {
        return $this->hasMany(EntradaInventario::class, 'almacen_id');
    }

    /**
     * Relación con las salidas de inventario.
     */
    public function salidasInventario(): HasMany
    {
        return $this->hasMany(SalidaInventario::class, 'almacen_id');
    }

    /**
     * Relación con los inventarios de productos.
     */
    public function inventariosProductos(): HasMany
    {
        return $this->hasMany(InventarioProducto::class, 'almacen_id');
    }

    /**
     * Scope para filtrar almacenes activos.
     */
    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para filtrar por sucursal.
     */
    public function scopeDeSucursal($query, $sucursalId)
    {
        return $query->where('sucursal_id', $sucursalId);
    }

    /**
     * Obtiene el stock actual de un producto en este almacén desde inventario_productos.
     */
    public function getStockProducto($productoId): float
    {
        $inventario = $this->inventariosProductos()
            ->where('producto_id', $productoId)
            ->first();

        return $inventario ? (float) $inventario->stock_actual : 0.0;
    }
}
