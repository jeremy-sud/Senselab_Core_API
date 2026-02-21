<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
/** @use HasFactory<\Database\Factories\PagoNominaFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `pagos_nomina`.
 * Generado a partir del CREATE TABLE real obtenido de la BD.
 */
class PagoNomina extends Model
{
    /** @use HasFactory<\Database\Factories\PagoNominaFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'pagos_nomina';

    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'empleado_id',
        'periodo_nomina_id',
        'fecha_pago',
        'monto_bruto',
        'total_deducciones',
        'monto_neto_pagado',
        'metodo_pago_id',
        'referencia_pago',
        'estado',
        'observaciones',
        'activo',
        'eliminado',
        'creado_en',
        'actualizado_en',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto_bruto' => 'decimal:2',
        'total_deducciones' => 'decimal:2',
        'monto_neto_pagado' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**


     * @var array<string, mixed>


     */

    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'empleado_id' => 'required|exists:empleados,id',
        'fecha_pago' => 'required|date',
        'monto_bruto' => 'required|numeric|min:0',
    ];

    /* --------------------- Relaciones --------------------- */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function empleado(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function periodoNomina(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(PeriodoNomina::class, 'periodo_nomina_id');
    }

    /**
     * Relación con la forma de pago (Alias para metodoPago).
     * Se agrega para consistencia con otros modelos que usan formaPago.
     */
    public function formaPago(): mixed
    {
        return $this->belongsTo(FormaPago::class, 'metodo_pago_id');
    }

    /* --------------------- Scopes --------------------- */
    public function scopeActivos(mixed $q): Builder{
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorEmpleado(mixed $q, mixed $empleadoId): Builder{
        return $q->where('empleado_id', $empleadoId);
    }

    public function scopePorPeriodo(mixed $q, mixed $periodoId): Builder{
        return $q->where('periodo_nomina_id', $periodoId);
    }

    public function scopeRecientes(mixed $q, mixed $limit = 10): Builder{
        return $q->orderBy('fecha_pago', 'desc')->limit($limit);
    }

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (PagoNomina $p) {
            // Calcular monto neto si no viene explícito
            if (!isset($p->monto_neto_pagado) || $p->monto_neto_pagado === null) {
                $bruto = (float) ($p->monto_bruto ?? 0);
                $dedu = (float) ($p->total_deducciones ?? 0);
                $p->monto_neto_pagado = round($bruto - $dedu, 2);
            }

            if (isset($p->referencia_pago)) {
                $p->referencia_pago = trim($p->referencia_pago);
            }

            if (isset($p->estado)) {
                $p->estado = Str::lower(trim($p->estado));
            }
        });
    }

    /* --------------------- Helpers --------------------- */
    public function esPagoFinalizado(): mixed
    {
        return in_array($this->estado, ['pagado', 'confirmado']);
    }
}
