<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo para Líneas de Detalle de Comprobantes Electrónicos
 * 
 * Representa cada producto/servicio incluido en un comprobante electrónico.
 */
class FeLineaDetalle extends Model
{
    use HasFactory;

    /**
     * Nombre de la tabla en la base de datos.
     */
    protected $table = 'fe_lineas_detalle';

    /**
     * Atributos asignables en masa.
     */
    protected $fillable = [
        'comprobante_id',
        'numero_linea',
        'codigo_tipo',
        'codigo',
        'codigo_comercial',
        'detalle',
        'cantidad',
        'unidad_medida',
        'unidad_medida_comercial',
        'precio_unitario',
        'monto_total',
        'monto_descuento',
        'naturaleza_descuento',
        'subtotal',
        'base_imponible',
        'impuesto_codigo',
        'impuesto_codigo_tarifa',
        'impuesto_tarifa',
        'impuesto_monto',
        'exoneracion_tipo_documento',
        'exoneracion_numero_documento',
        'exoneracion_nombre_institucion',
        'exoneracion_fecha_emision',
        'exoneracion_porcentaje',
        'exoneracion_monto',
        'monto_total_linea',
        'metadata',
    ];

    /**
     * Atributos que deben ser casteados a tipos nativos.
     */
    protected $casts = [
        'numero_linea' => 'integer',
        'cantidad' => 'decimal:5',
        'precio_unitario' => 'decimal:5',
        'monto_total' => 'decimal:5',
        'monto_descuento' => 'decimal:5',
        'subtotal' => 'decimal:5',
        'base_imponible' => 'decimal:5',
        'impuesto_tarifa' => 'decimal:2',
        'impuesto_monto' => 'decimal:5',
        'exoneracion_fecha_emision' => 'date',
        'exoneracion_porcentaje' => 'decimal:2',
        'exoneracion_monto' => 'decimal:5',
        'monto_total_linea' => 'decimal:5',
        'metadata' => 'array',
    ];

    /**
     * Relación: Pertenece a un Comprobante Electrónico.
     */
    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(ComprobanteElectronicoFe::class, 'comprobante_id');
    }

    /**
     * Accessor: Verificar si la línea tiene descuento.
     */
    public function getTieneDescuentoAttribute(): bool
    {
        return $this->monto_descuento > 0;
    }

    /**
     * Accessor: Verificar si la línea tiene impuesto.
     */
    public function getTieneImpuestoAttribute(): bool
    {
        return $this->impuesto_monto > 0;
    }

    /**
     * Accessor: Verificar si la línea tiene exoneración.
     */
    public function getTieneExoneracionAttribute(): bool
    {
        return $this->exoneracion_monto > 0;
    }
}
