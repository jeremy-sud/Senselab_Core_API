<?php

namespace App\Models;

use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `tipos_clientes`.
 * Catálogo de tipos de clientes (Mayorista, Minorista, Gobierno, etc.).
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class TipoCliente extends Model
{
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

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'tipo_cliente_id');
    }

    /* --------------------- Scopes --------------------- */

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopeConDescuento($query)
    {
        return $query->where('descuento_default', '>', 0);
    }

    public function scopeConCredito($query)
    {
        return $query->where('dias_credito_default', '>', 0);
    }

    /* --------------------- Métodos --------------------- */

    public function tieneDescuento()
    {
        return $this->descuento_default > 0;
    }

    public function tieneCredito()
    {
        return $this->dias_credito_default > 0;
    }
}
