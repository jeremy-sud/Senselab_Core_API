<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
/** @use HasFactory<\Database\Factories\DeduccionLegalFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `deducciones_legales`.
 * Catálogo de deducciones legales aplicables a nómina (CCSS, INS, LPT, etc.).
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class DeduccionLegal extends Model
{
    /** @use HasFactory<\Database\Factories\DeduccionLegalFactory> */
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'deducciones_legales';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'tipo',
        'porcentaje_base',
        'monto_fijo',
        'aplica_sobre',
        'es_obligatoria',
        'activa',
        'eliminado',
    ];

    protected $casts = [
        'porcentaje_base' => 'decimal:2',
        'monto_fijo' => 'decimal:2',
        'es_obligatoria' => 'boolean',
        'activa' => 'boolean',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* --------------------- Scopes --------------------- */

    public function scopeActivas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('activa', true)->where('eliminado', false);
    }

    public function scopeObligatorias(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('es_obligatoria', true);
    }

    public function scopePorTipo(Builder $query, mixed $tipo): Builder{
        return $query->where('tipo', $tipo);
    }

    /* --------------------- Métodos --------------------- */

    public function esCCSS(): mixed
    {
        return in_array($this->tipo, ['ccss_obrero', 'ccss_patronal']);
    }

    public function esINS(): mixed
    {
        return in_array($this->tipo, ['ins_laboral', 'ins_lpt']);
    }

    public function calcularMonto(mixed $salario): mixed
    {
        if ($this->monto_fijo) {
            return $this->monto_fijo;
        }

        if ($this->porcentaje_base) {
            return ($salario * $this->porcentaje_base) / 100;
        }

        return 0;
    }

    public function esObligatoria(): mixed
    {
        return $this->es_obligatoria === true;
    }
}
