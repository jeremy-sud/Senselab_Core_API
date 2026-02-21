<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\MovimientoBancarioFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `movimientos_bancarios`.
 * Gestiona movimientos bancarios (depósitos, retiros, transferencias).
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class MovimientoBancario extends Model
{
    /** @use HasFactory<\Database\Factories\MovimientoBancarioFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'movimientos_bancarios';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'cuenta_bancaria_id',
        'empresa_id',
        'fecha_movimiento',
        'fecha_valor',
        'tipo_movimiento',
        'numero_referencia',
        'descripcion',
        'monto',
        'saldo_despues',
        'beneficiario',
        'conciliado',
        'fecha_conciliacion',
        'asiento_contable_id',
        'notas',
        'eliminado',
    ];

    protected $casts = [
        'fecha_movimiento' => 'date',
        'fecha_valor' => 'date',
        'fecha_conciliacion' => 'date',
        'monto' => 'decimal:2',
        'saldo_despues' => 'decimal:2',
        'conciliado' => 'boolean',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* --------------------- Relaciones --------------------- */

    public function cuentaBancaria(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CuentaBancaria::class);
    }

    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function asientoContable(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(AsientoContable::class);
    }

    /* --------------------- Scopes --------------------- */

    public function scopeConciliados(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('conciliado', true);
    }

    public function scopePendientesConciliacion(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('conciliado', false);
    }

    public function scopePorTipo(Builder $query, mixed $tipo): Builder{
        return $query->where('tipo_movimiento', $tipo);
    }

    public function scopeEntreFechas(Builder $query, mixed $desde, mixed $hasta): Builder{
        return $query->whereBetween('fecha_movimiento', [$desde, $hasta]);
    }

    /* --------------------- Métodos --------------------- */

    public function esDeposito(): mixed
    {
        return in_array($this->tipo_movimiento, ['deposito', 'transferencia_entrada', 'interes']);
    }

    public function esRetiro(): mixed
    {
        return in_array($this->tipo_movimiento, ['retiro', 'transferencia_salida', 'comision']);
    }

    public function estaConciliado(): mixed
    {
        return $this->conciliado === true;
    }

    public function conciliar(): void
    {
        $this->update([
            'conciliado' => true,
            'fecha_conciliacion' => now(),
        ]);
    }
}
