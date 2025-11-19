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
        return $this->belongsTo(PeriodoNomina::class, 'periodo_nomina_id');
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
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

    public function calcularTotales(): void
    {
        $this->total_devengado = $this->salario_bruto + $this->monto_horas_extras + $this->bonificaciones;
        $this->total_deducciones = $this->deducciones_ccss + $this->deducciones_impuesto_renta + $this->otras_deducciones;
        $this->salario_neto = $this->total_devengado - $this->total_deducciones;
    }
}
