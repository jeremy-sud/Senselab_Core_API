<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\ZonaGeograficaFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `zonas_geograficas`.
 * Gestiona zonas geográficas (provincias, cantones, zonas de ventas, rutas).
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class ZonaGeografica extends Model
{
    /** @use HasFactory<\Database\Factories\ZonaGeograficaFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'zonas_geograficas';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'tipo',
        'zona_padre_id',
        'provincias_incluidas',
        'vendedor_asignado_id',
        'activa',
        'eliminado',
    ];

    protected $casts = [
        'provincias_incluidas' => 'array',
        'activa' => 'boolean',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* --------------------- Relaciones --------------------- */

    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function zonaPadre(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(ZonaGeografica::class, 'zona_padre_id');
    }

    public function zonasHijas(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ZonaGeografica::class, 'zona_padre_id');
    }

    public function vendedorAsignado(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'vendedor_asignado_id');
    }

    /* --------------------- Scopes --------------------- */

    public function scopeActivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activa', true)->where('eliminado', false);
    }

    public function scopePorTipo(Builder $query, mixed $tipo): Builder{
        return $query->where('tipo', $tipo);
    }

    public function scopeProvincias(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tipo', 'provincia');
    }

    public function scopeCantones(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tipo', 'canton');
    }

    public function scopeZonasVentas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tipo', 'zona_ventas');
    }

    /* --------------------- Métodos --------------------- */

    public function esProvincia(): mixed
    {
        return $this->tipo === 'provincia';
    }

    public function esCanton(): mixed
    {
        return $this->tipo === 'canton';
    }

    public function esZonaVentas(): mixed
    {
        return $this->tipo === 'zona_ventas';
    }

    public function tieneVendedorAsignado(): mixed
    {
        return !is_null($this->vendedor_asignado_id);
    }
}
