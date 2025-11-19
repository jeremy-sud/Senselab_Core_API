<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCuentaPagar extends Model
{
    use HasFactory;

    protected $table = 'pagos_cuentas_pagar';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'cuenta_por_pagar_id',
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

    public function cuentaPorPagar(): BelongsTo
    {
        return $this->belongsTo(CuentaPorPagar::class);
    }

    public function formaPago(): BelongsTo
    {
        return $this->belongsTo(FormaPago::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorCuenta($query, $cuentaId)
    {
        return $query->where('cuenta_por_pagar_id', $cuentaId);
    }

    public function scopePorFormaPago($query, $formaPagoId)
    {
        return $query->where('forma_pago_id', $formaPagoId);
    }

    public function scopeFechaBetween($query, $desde, $hasta)
    {
        return $query->whereBetween('fecha_pago', [$desde, $hasta]);
    }
}
