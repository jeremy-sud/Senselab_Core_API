<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\CajaFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Caja extends Model
{
    /** @use HasFactory<\Database\Factories\CajaFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'cajas';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'sucursal_id',
        'nombre',
        'descripcion',
        'activo',
        'eliminado'
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
     * Relación con la sucursal.
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * Scope para obtener cajas activas.
     */
    public function scopeActivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para buscar por nombre.
     */
    public function scopePorNombre(Builder $query, mixed $nombre): Builder{
        return $query->where('nombre', 'LIKE', "%{$nombre}%");
    }

    /**
     * Scope para filtrar por sucursal.
     */
    public function scopePorSucursal(Builder $query, mixed $sucursalId): Builder{
        return $query->where('sucursal_id', $sucursalId);
    }
}