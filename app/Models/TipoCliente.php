<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\TipoClienteFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `tipos_clientes`.
 * Catálogo de tipos de clientes (Mayorista, Minorista, Gobierno, etc.).
 *
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Senselab
 */
class TipoCliente extends Model
{
    /** @use HasFactory<\Database\Factories\TipoClienteFactory> */
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'tipos_clientes';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'descuento_default',
        'dias_credito_default',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'descuento_default' => 'decimal:2',
        'dias_credito_default' => 'integer',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* --------------------- Relaciones --------------------- */

    public function clientes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Cliente::class, 'tipo_cliente_id');
    }

    /* --------------------- Scopes --------------------- */

    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopeConDescuento(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('descuento_default', '>', 0);
    }

    public function scopeConCredito(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('dias_credito_default', '>', 0);
    }

    /* --------------------- Métodos --------------------- */

    public function tieneDescuento(): mixed
    {
        return $this->descuento_default > 0;
    }

    public function tieneCredito(): mixed
    {
        return $this->dias_credito_default > 0;
    }
}
