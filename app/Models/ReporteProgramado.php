<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
/** @use HasFactory<\Database\Factories\ReporteProgramadoFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasActiveScope;

class ReporteProgramado extends Model
{
    /** @use HasFactory<\Database\Factories\ReporteProgramadoFactory> */
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasActiveScope;

    protected $table = 'reportes_programados';

    const CREATED_AT = 'creado_en';
    const UPDATED_AT = 'actualizado_en';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'nombre',
        'tipo_reporte',
        'frecuencia',
        'formato',
        'filtros',
        'destinatarios',
        'dia_semana',
        'dia_mes',
        'hora_envio',
        'ultima_ejecucion',
        'proxima_ejecucion',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'filtros' => 'array',
        'destinatarios' => 'array',
        'dia_mes' => 'integer',
        'ultima_ejecucion' => 'datetime',
        'proxima_ejecucion' => 'datetime',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'creado_en' => 'datetime',
        'actualizado_en' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id')->withoutGlobalScopes();
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function scopePendientesEjecucion(Builder $query): Builder
    {
        return $query->where('activo', true)
            ->where('eliminado', false)
            ->where(function (Builder $q) {
                $q->whereNull('proxima_ejecucion')
                  ->orWhere('proxima_ejecucion', '<=', now());
            });
    }
}
