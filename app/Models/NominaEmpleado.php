<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NominaEmpleado extends Model
{
    use HasFactory;

    protected $table = 'nomina_empleados';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'periodo_nomina_id',
        'empleado_id',
        'salario_bruto',
        'horas_extras',
        'monto_horas_extras',
        'bonificaciones',
        'total_devengado',
        'deducciones_ccss',
        'deducciones_impuesto_renta',
        'otras_deducciones',
        'total_deducciones',
        'salario_neto',
        'observaciones',
        'activo',
        'eliminado'
    ];

    protected $casts = [
        'salario_bruto' => 'decimal:2',
        'horas_extras' => 'decimal:2',
        'monto_horas_extras' => 'decimal:2',
        'bonificaciones' => 'decimal:2',
        'total_devengado' => 'decimal:2',
        'deducciones_ccss' => 'decimal:2',
        'deducciones_impuesto_renta' => 'decimal:2',
        'otras_deducciones' => 'decimal:2',
        'total_deducciones' => 'decimal:2',
        'salario_neto' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime'
    ];

    public function periodoNomina(): BelongsTo
    {
        return $this->belongsTo(PeriodoNomina::class);
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorPeriodo($query, $periodoId)
    {
        return $query->where('periodo_nomina_id', $periodoId);
    }

    public function scopePorEmpleado($query, $empleadoId)
    {
        return $query->where('empleado_id', $empleadoId);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($nomina) {
            $nomina->total_devengado = $nomina->salario_bruto + $nomina->monto_horas_extras + $nomina->bonificaciones;
            $nomina->total_deducciones = $nomina->deducciones_ccss + $nomina->deducciones_impuesto_renta + $nomina->otras_deducciones;
            $nomina->salario_neto = $nomina->total_devengado - $nomina->total_deducciones;
        });
    }
}
