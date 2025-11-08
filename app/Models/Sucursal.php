<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * Estructura organizacional de cada empresa (matriz, sucursales, oficinas).
     */
    protected $table = 'sucursales';

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
        'direccion',
        'telefono',
        'email',
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
     * Relación con la empresa propietaria.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')
                    ->withoutGlobalScopes();
    }

    /**
     * Relación con los almacenes de la sucursal.
     */
    public function almacenes(): HasMany
    {
        return $this->hasMany(Almacen::class);
    }

    /**
     * Scope para filtrar sucursales activas.
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }
}
