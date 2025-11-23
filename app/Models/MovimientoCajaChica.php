<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;

class MovimientoCajaChica extends Model
{
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    /**
     * La tabla asociada al modelo.
     */
    protected $table = 'movimientos_caja_chica';

    /**
     * Nombre personalizado para los timestamps
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'caja_chica_id',
        'fecha_movimiento',
        'tipo_movimiento',
        'monto',
        'numero_comprobante',
        'concepto',
        'cuenta_contable_id',
        'activo',
        'eliminado'
    ];

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'fecha_movimiento' => 'date',
        'monto' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    /**
     * Tipos de movimientos permitidos.
     */
    const TIPO_INGRESO = 'Ingreso';
    const TIPO_EGRESO = 'Egreso';
    const TIPO_REEMBOLSO = 'Reembolso';
    const TIPO_AJUSTE = 'Ajuste';

    /**
     * Relación con la caja chica.
     */
    public function cajaChica(): BelongsTo
    {
        return $this->belongsTo(CajaChica::class, 'caja_chica_id');
    }

    /**
     * Relación con la cuenta contable.
     */
    public function cuentaContable(): BelongsTo
    {
        return $this->belongsTo(CuentaContable::class, 'cuenta_contable_id');
    }

    /**
     * Scope para obtener movimientos activos.
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true)
                    ->where('eliminado', false);
    }

    /**
     * Scope para filtrar por caja chica.
     */
    public function scopePorCaja($query, $cajaChicaId)
    {
        return $query->where('caja_chica_id', $cajaChicaId);
    }

    /**
     * Scope para filtrar por tipo de movimiento.
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_movimiento', $tipo);
    }

    /**
     * Scope para filtrar por rango de fechas.
     */
    public function scopeFechaBetween($query, $start, $end)
    {
        return $query->whereBetween('fecha_movimiento', [$start, $end]);
    }

    /**
     * Determina si el movimiento es un ingreso.
     */
    public function esIngreso(): bool
    {
        return $this->tipo_movimiento === self::TIPO_INGRESO;
    }

    /**
     * Determina si el movimiento es un egreso.
     */
    public function esEgreso(): bool
    {
        return $this->tipo_movimiento === self::TIPO_EGRESO;
    }

    /**
     * Determina si el movimiento es un reembolso.
     */
    public function esReembolso(): bool
    {
        return $this->tipo_movimiento === self::TIPO_REEMBOLSO;
    }

    /**
     * Determina si el movimiento es un ajuste.
     */
    public function esAjuste(): bool
    {
        return $this->tipo_movimiento === self::TIPO_AJUSTE;
    }

    /**
     * Obtiene el monto con signo según el tipo de movimiento.
     */
    public function getMontoSignedAttribute(): float
    {
        if ($this->esEgreso()) {
            return -$this->monto;
        }
        return $this->monto;
    }
}
