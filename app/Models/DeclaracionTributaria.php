<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `declaraciones_tributarias`.
 * Gestiona declaraciones de impuestos (IVA, Renta, etc.) ante Hacienda.
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class DeclaracionTributaria extends Model
{
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'declaraciones_tributarias';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'empresa_id',
        'tipo_declaracion',
        'periodo_fiscal',
        'fecha_inicio_periodo',
        'fecha_fin_periodo',
        'fecha_presentacion',
        'monto_base_imponible',
        'monto_impuesto',
        'monto_creditos',
        'monto_debitos',
        'monto_a_pagar',
        'monto_a_favor',
        'numero_confirmacion',
        'archivo_xml',
        'archivo_pdf',
        'estado',
        'notas',
        'eliminado',
    ];

    protected $casts = [
        'fecha_inicio_periodo' => 'date',
        'fecha_fin_periodo' => 'date',
        'fecha_presentacion' => 'date',
        'monto_base_imponible' => 'decimal:2',
        'monto_impuesto' => 'decimal:2',
        'monto_creditos' => 'decimal:2',
        'monto_debitos' => 'decimal:2',
        'monto_a_pagar' => 'decimal:2',
        'monto_a_favor' => 'decimal:2',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* --------------------- Relaciones --------------------- */

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
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

    public function scopeAceptadas($query)
    {
        return $query->where('estado', 'aceptada');
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_declaracion', $tipo);
    }

    public function scopePorPeriodo($query, $periodo)
    {
        return $query->where('periodo_fiscal', $periodo);
    }

    /* --------------------- Métodos --------------------- */

    public function esIVA()
    {
        return $this->tipo_declaracion === 'D104';
    }

    public function esRenta()
    {
        return $this->tipo_declaracion === 'D101';
    }

    public function esBorrador()
    {
        return $this->estado === 'borrador';
    }

    public function fueAceptada()
    {
        return $this->estado === 'aceptada';
    }

    public function calcularSaldoNeto()
    {
        return $this->monto_a_pagar - $this->monto_a_favor;
    }
}
