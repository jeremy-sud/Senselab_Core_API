<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
/** @use HasFactory<\Database\Factories\PagoCuentaCobrarFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class PagoCuentaCobrar extends Model
{
    /** @use HasFactory<\Database\Factories\PagoCuentaCobrarFactory> */
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'pagos_cuentas_cobrar';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'cuenta_por_cobrar_id',
        'forma_pago_id',
        'fecha_pago',
        'monto_pago',
        'numero_referencia',
        'moneda',
        'observaciones',
        'activo',
        'eliminado'
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto_pago' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    public function cuentaPorCobrar(): BelongsTo
    {
        return $this->belongsTo(CuentaPorCobrar::class);
    }

    public function formaPago(): BelongsTo
    {
        return $this->belongsTo(FormaPago::class);
    }

    public function scopeActivos(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorCuenta(Builder $query, mixed $cuentaId): Builder{
        return $query->where('cuenta_por_cobrar_id', $cuentaId);
    }

    public function scopePorFormaPago(Builder $query, mixed $formaPagoId): Builder{
        return $query->where('forma_pago_id', $formaPagoId);
    }

    public function scopeFechaBetween(Builder $query, mixed $desde, mixed $hasta): Builder{
        return $query->whereBetween('fecha_pago', [$desde, $hasta]);
    }
}
