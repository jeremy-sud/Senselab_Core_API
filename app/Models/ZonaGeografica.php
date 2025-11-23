<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
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

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function zonaPadre()
    {
        return $this->belongsTo(ZonaGeografica::class, 'zona_padre_id');
    }

    public function zonasHijas()
    {
        return $this->hasMany(ZonaGeografica::class, 'zona_padre_id');
    }

    public function vendedorAsignado()
    {
        return $this->belongsTo(Empleado::class, 'vendedor_asignado_id');
    }

    /* --------------------- Scopes --------------------- */

    public function scopeActivas($query)
    {
        return $query->where('activa', true)->where('eliminado', false);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function scopeProvincias($query)
    {
        return $query->where('tipo', 'provincia');
    }

    public function scopeCantones($query)
    {
        return $query->where('tipo', 'canton');
    }

    public function scopeZonasVentas($query)
    {
        return $query->where('tipo', 'zona_ventas');
    }

    /* --------------------- Métodos --------------------- */

    public function esProvincia()
    {
        return $this->tipo === 'provincia';
    }

    public function esCanton()
    {
        return $this->tipo === 'canton';
    }

    public function esZonaVentas()
    {
        return $this->tipo === 'zona_ventas';
    }

    public function tieneVendedorAsignado()
    {
        return !is_null($this->vendedor_asignado_id);
    }
}
