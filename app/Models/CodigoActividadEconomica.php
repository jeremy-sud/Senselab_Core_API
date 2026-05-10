<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\CodigoActividadEconomicaFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `codigos_actividad_economica`.
 * Catálogo de códigos de actividad económica de Costa Rica.
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class CodigoActividadEconomica extends Model
{
    /** @use HasFactory<\Database\Factories\CodigoActividadEconomicaFactory> */
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'codigos_actividad_economica';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'codigo',
        'descripcion',
        'categoria_principal',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* --------------------- Scopes --------------------- */

    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorCategoria(Builder $query, mixed $categoria): Builder{
        return $query->where('categoria_principal', $categoria);
    }

    public function scopeBuscar(Builder $query, mixed $termino): Builder{
        return $query->where(function ($q) use ($termino) {
            $q->where('codigo', 'like', "%{$termino}%")
              ->orWhere('descripcion', 'like', "%{$termino}%");
        });
    }
}
