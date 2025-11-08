<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Almacen extends Model
{
    use HasFactory, BelongsToTenant;

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
        'nombre',
        'sucursal_id',
        'descripcion',
        'direccion',
        'activo'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
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
     * Scope para filtrar almacenes activos.
     */
    public function scopeActivos($query)
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
     * Obtiene el stock actual de un producto en este almacén.
     */
    public function getStockProducto($productoId): float
    {
        $entradas = $this->entradasInventario()
            ->where('producto_id', $productoId)
            ->sum('cantidad');

        $salidas = $this->salidasInventario()
            ->where('producto_id', $productoId)
            ->sum('cantidad');

        return $entradas - $salidas;
    }
}
