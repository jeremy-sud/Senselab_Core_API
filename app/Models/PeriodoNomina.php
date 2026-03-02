<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
/** @use HasFactory<\Database\Factories\PeriodoNominaFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Support\Str;

/**
 * Modelo para la tabla `periodos_nomina`.
 * Generado a partir del SHOW CREATE TABLE obtenido.
 */
class PeriodoNomina extends Model
{
    /** @use HasFactory<\Database\Factories\PeriodoNominaFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'periodos_nomina';

    public $timestamps = true;

    /**
     * Nombres personalizados de las marcas de tiempo.
     */
    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'nombre_periodo',
        'fecha_inicio',
        'fecha_fin',
        'fecha_pago',
        'estado',
        'total_salarios',
        'total_deducciones',
        'total_neto',
        'observaciones',
        'activo',
        'eliminado',
        'creado_en',
        'actualizado_en',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'fecha_pago' => 'date',
        'total_salarios' => 'decimal:2',
        'total_deducciones' => 'decimal:2',
        'total_neto' => 'decimal:2',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
    ];

    /**


     * @var array<string, mixed>


     */

    public static $rules = [
        'empresa_id' => 'required|exists:empresas,id',
        'nombre_periodo' => 'required|string',
        'fecha_inicio' => 'required|date',
        'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
    ];

    /* --------------------- Relaciones --------------------- */
    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function pagosNomina(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PagoNomina::class, 'periodo_nomina_id');
    }

    /**
     * Alias: pagos del período (usado en GeneratePdfReportJob).
     */
    public function pagos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->pagosNomina();
    }

    /* --------------------- Scopes --------------------- */
    public function scopeActivos(mixed $q): Builder{
        return $q->where('activo', true)->where('eliminado', false);
    }

    public function scopePorEmpresa(mixed $q, mixed $empresaId): Builder{
        return $q->where('empresa_id', $empresaId);
    }

    public function scopeAbiertos(mixed $q): Builder{
        return $q->where('estado', 'Abierto');
    }

    /* --------------------- Boot / eventos --------------------- */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (PeriodoNomina $p) {
            // Normalizar nombre y estado
            if (isset($p->nombre_periodo)) {
                $p->nombre_periodo = trim($p->nombre_periodo);
            }

            if (isset($p->estado)) {
                $p->estado = Str::ucfirst(Str::lower(trim($p->estado)));
            }

            // Coherencia de fechas
            if (isset($p->fecha_inicio, $p->fecha_fin)) {
                if ($p->fecha_fin < $p->fecha_inicio) {
                    throw new \InvalidArgumentException('fecha_fin debe ser mayor o igual a fecha_inicio');
                }
            }
        });
    }

    /* --------------------- Helpers --------------------- */
    public function duracionDias(): mixed
    {
        if (!$this->fecha_inicio || !$this->fecha_fin) {
            return null;
        }

        return $this->fecha_fin->diffInDays($this->fecha_inicio) + 1;
    }
}
