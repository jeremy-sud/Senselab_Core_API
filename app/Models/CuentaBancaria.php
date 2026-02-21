<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\CuentaBancariaFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `cuentas_bancarias`.
 * Gestiona cuentas bancarias de las empresas.
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class CuentaBancaria extends Model
{
    /** @use HasFactory<\Database\Factories\CuentaBancariaFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'cuentas_bancarias';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'empresa_id',
        'banco',
        'numero_cuenta',
        'iban',
        'tipo_cuenta',
        'moneda',
        'saldo_actual',
        'cuenta_contable_id',
        'sucursal_banco',
        'contacto_ejecutivo',
        'telefono_ejecutivo',
        'activa',
        'es_principal',
        'notas',
        'eliminado',
    ];

    protected $casts = [
        'saldo_actual' => 'decimal:2',
        'activa' => 'boolean',
        'es_principal' => 'boolean',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'numero_cuenta', // Ocultar por seguridad
    ];

    /* --------------------- Relaciones --------------------- */

    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cuentaContable(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CuentaContable::class);
    }

    public function movimientos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MovimientoBancario::class);
    }

    /* --------------------- Scopes --------------------- */

    public function scopeActivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activa', true)->where('eliminado', false);
    }

    public function scopePorMoneda(Builder $query, mixed $moneda): Builder{
        return $query->where('moneda', $moneda);
    }

    public function scopePrincipales(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('es_principal', true);
    }

    /* --------------------- Métodos --------------------- */

    public function getNumeroCuentaEnmascarado(): mixed
    {
        $numero = $this->numero_cuenta;
        $longitud = strlen($numero);
        if ($longitud > 4) {
            return str_repeat('*', $longitud - 4) . substr($numero, -4);
        }
        return $numero;
    }

    public function actualizarSaldo(mixed $monto): void
    {
        $this->increment('saldo_actual', $monto);
    }

    public function estaActiva(): mixed
    {
        return $this->activa === true;
    }
}
