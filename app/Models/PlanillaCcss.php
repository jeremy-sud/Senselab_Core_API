<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `planillas_ccss`.
 * Gestiona planillas de CCSS mensuales por empresa.
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class PlanillaCcss extends Model
{
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'planillas_ccss';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'empresa_id',
        'periodo_nomina_id',
        'periodo',
        'fecha_generacion',
        'fecha_presentacion',
        'numero_planilla',
        'total_empleados',
        'total_salarios',
        'total_cuota_obrera',
        'total_cuota_patronal',
        'total_a_pagar',
        'archivo_xml',
        'archivo_pdf',
        'estado',
        'fecha_pago',
        'notas',
        'eliminado',
    ];

    protected $casts = [
        'fecha_generacion' => 'date',
        'fecha_presentacion' => 'date',
        'fecha_pago' => 'date',
        'total_empleados' => 'integer',
        'total_salarios' => 'decimal:2',
        'total_cuota_obrera' => 'decimal:2',
        'total_cuota_patronal' => 'decimal:2',
        'total_a_pagar' => 'decimal:2',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* --------------------- Relaciones --------------------- */

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function periodoNomina()
    {
        return $this->belongsTo(PeriodoNomina::class);
    }

    /* --------------------- Scopes --------------------- */

    public function scopeBorradores($query)
    {
        return $query->where('estado', 'borrador');
    }

    public function scopeEnviadas($query)
    {
        return $query->where('estado', 'enviada');
    }

    public function scopePagadas($query)
    {
        return $query->where('estado', 'pagada');
    }

    public function scopePorPeriodo($query, $periodo)
    {
        return $query->where('periodo', $periodo);
    }

    /* --------------------- Métodos --------------------- */

    public function esBorrador()
    {
        return $this->estado === 'borrador';
    }

    public function fuePagada()
    {
        return $this->estado === 'pagada';
    }

    public function calcularTotalCuotas()
    {
        return $this->total_cuota_obrera + $this->total_cuota_patronal;
    }

    public function marcarComoPagada()
    {
        $this->update([
            'estado' => 'pagada',
            'fecha_pago' => now(),
        ]);
    }
}
