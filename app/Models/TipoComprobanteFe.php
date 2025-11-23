<?php

namespace App\Models;

use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `tipos_comprobantes_fe`.
 * Catálogo de tipos de comprobantes según DGT Costa Rica.
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class TipoComprobanteFe extends Model
{
    use HasFactory, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'tipos_comprobantes_fe';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'codigo_dgt',
        'nombre',
        'descripcion',
        'requiere_referencia',
        'permite_exportacion',
        'activo',
        'eliminado',
    ];

    protected $casts = [
        'requiere_referencia' => 'boolean',
        'permite_exportacion' => 'boolean',
        'activo' => 'boolean',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* --------------------- Scopes --------------------- */

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->where('eliminado', false);
    }

    public function scopePorCodigo($query, $codigo)
    {
        return $query->where('codigo_dgt', $codigo);
    }

    public function scopeQueRequierenReferencia($query)
    {
        return $query->where('requiere_referencia', true);
    }

    public function scopePermiteExportacion($query)
    {
        return $query->where('permite_exportacion', true);
    }

    /* --------------------- Métodos --------------------- */

    public function esFacturaElectronica()
    {
        return $this->codigo_dgt === '01';
    }

    public function esNotaCredito()
    {
        return $this->codigo_dgt === '03';
    }

    public function esNotaDebito()
    {
        return $this->codigo_dgt === '02';
    }

    public function esTiquete()
    {
        return $this->codigo_dgt === '04';
    }
}
