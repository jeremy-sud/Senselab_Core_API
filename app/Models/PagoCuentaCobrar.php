<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCuentaCobrar extends Model
{
    use HasFactory;

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
        return $this->belongsTo(CuentaPorCobrar::class, 'cuenta_por_cobrar_id');
    }

    public function formaPago(): BelongsTo
    {
        return $this->belongsTo(FormaPago::class, 'forma_pago_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorCuenta($query, $cuentaId)
    {
        return $query->where('cuenta_por_cobrar_id', $cuentaId);
    }

    public function scopeFechaBetween($query, $start, $end)
    {
        return $query->whereBetween('fecha_pago', [$start, $end]);
    }
}
