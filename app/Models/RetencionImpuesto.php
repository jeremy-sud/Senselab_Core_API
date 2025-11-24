<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\HasCustomSoftDeletes;
use App\Traits\HasAuditFields;
use App\Traits\HasActiveScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `retenciones_impuestos`.
 * Gestiona retenciones de impuestos (renta, IVA) aplicadas a proveedores.
 * 
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
class RetencionImpuesto extends Model
{
    use HasFactory, BelongsToTenant, HasCustomSoftDeletes, HasAuditFields, HasActiveScope;

    protected $table = 'retenciones_impuestos';
    public $timestamps = true;

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'compra_id',
        'venta_id',
        'tipo_retencion',
        'porcentaje_retencion',
        'monto_base',
        'monto_retenido',
        'numero_comprobante',
        'fecha_retencion',
        'periodo_declaracion',
        'declarado',
        'notas',
        'eliminado',
    ];

    protected $casts = [
        'porcentaje_retencion' => 'decimal:2',
        'monto_base' => 'decimal:2',
        'monto_retenido' => 'decimal:2',
        'fecha_retencion' => 'date',
        'declarado' => 'boolean',
        'eliminado' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* --------------------- Relaciones --------------------- */

    public function empresa(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function proveedor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    /* --------------------- Scopes --------------------- */

    public function scopeDeclaradas(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('declarado', true);
    }

    public function scopePendientesDeclaracion(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('declarado', false);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_retencion', $tipo);
    }

    public function scopePorPeriodo($query, $periodo)
    {
        return $query->where('periodo_declaracion', $periodo);
    }

    /* --------------------- Métodos --------------------- */

    public function esRetencionRenta()
    {
        return $this->tipo_retencion === 'renta';
    }

    public function esRetencionIVA()
    {
        return $this->tipo_retencion === 'iva';
    }

    public function fueDeclarada()
    {
        return $this->declarado === true;
    }

    public function marcarComoDeclarada()
    {
        $this->update(['declarado' => true]);
    }
}
