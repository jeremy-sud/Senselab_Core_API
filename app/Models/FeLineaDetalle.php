<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
/** @use HasFactory<\Database\Factories\FeLineaDetalleFactory> */
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Modelo para Líneas de Detalle de Comprobantes Electrónicos
 * 
 * Representa cada producto/servicio incluido en un comprobante electrónico.
 */
class FeLineaDetalle extends Model
{
    /** @use HasFactory<\Database\Factories\FeLineaDetalleFactory> */
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
        'partida_arancelaria',
        'codigo_tipo',
        'codigo',
        'codigo_cabys',
        'codigo_comercial',
        'detalle',
        'numero_vin_serie',
        'registro_medicamento',
        'forma_farmaceutica',
        'cantidad',
        'unidad_medida',
        'unidad_medida_comercial',
        'tipo_transaccion',
        'precio_unitario',
        'monto_total',
        'monto_descuento',
        'codigo_descuento',
        'codigo_descuento_otro',
        'naturaleza_descuento',
        'subtotal',
        'base_imponible',
        'impuesto_codigo',
        'impuesto_codigo_tarifa',
        'impuesto_tarifa',
        'impuesto_monto',
        'impuesto_neto',
        'factor_calculo_iva',
        'iva_cobrado_fabrica',
        'impuesto_asumido_emisor_fabrica',
        'monto_exportacion',
        'exoneracion_tipo_documento',
        'exoneracion_tipo_documento_otro',
        'exoneracion_numero_documento',
        'exoneracion_articulo',
        'exoneracion_inciso',
        'exoneracion_nombre_institucion',
        'exoneracion_nombre_institucion_otros',
        'exoneracion_fecha_emision',
        'exoneracion_porcentaje',
        'exoneracion_tarifa_exonerada',
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
        'impuesto_neto' => 'decimal:5',
        'factor_calculo_iva' => 'decimal:4',
        'impuesto_asumido_emisor_fabrica' => 'decimal:5',
        'monto_exportacion' => 'decimal:5',
        'exoneracion_fecha_emision' => 'date',
        'exoneracion_porcentaje' => 'decimal:2',
        'exoneracion_tarifa_exonerada' => 'decimal:2',
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
     * Relación: Tiene muchos impuestos (tabla normalizada).
     */
    public function impuestos(): HasMany
    {
        return $this->hasMany(FeLineaImpuesto::class, 'linea_detalle_id');
    }

    /**
     * Relación: Tiene muchos descuentos (tabla normalizada).
     */
    public function descuentos(): HasMany
    {
        return $this->hasMany(FeLineaDescuento::class, 'linea_detalle_id');
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
