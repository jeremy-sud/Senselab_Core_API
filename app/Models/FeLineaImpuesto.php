<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para impuestos por línea de detalle.
 *
 * Soporta múltiples impuestos por línea (hasta 1000) según spec Hacienda v4.4.
 * Incluye impuestos específicos (combustibles, alcohol, tabaco) y exoneración.
 */
class FeLineaImpuesto extends Model
{
    protected $table = 'fe_linea_impuestos';

    protected $fillable = [
        'linea_detalle_id',
        'codigo',
        'codigo_impuesto_otro',
        'codigo_tarifa_iva',
        'tarifa',
        'factor_calculo_iva',
        'monto',
        'monto_exportacion',
        'cantidad_unidad_medida',
        'porcentaje',
        'proporcion',
        'volumen_unidad_consumo',
        'impuesto_unidad',
        'exoneracion_tipo_documento',
        'exoneracion_tipo_documento_otro',
        'exoneracion_numero_documento',
        'exoneracion_articulo',
        'exoneracion_inciso',
        'exoneracion_nombre_institucion',
        'exoneracion_nombre_institucion_otros',
        'exoneracion_fecha_emision',
        'exoneracion_tarifa_exonerada',
        'exoneracion_monto',
    ];

    protected $casts = [
        'tarifa' => 'decimal:2',
        'factor_calculo_iva' => 'decimal:4',
        'monto' => 'decimal:5',
        'monto_exportacion' => 'decimal:5',
        'cantidad_unidad_medida' => 'decimal:2',
        'porcentaje' => 'decimal:2',
        'proporcion' => 'decimal:2',
        'volumen_unidad_consumo' => 'decimal:2',
        'impuesto_unidad' => 'decimal:5',
        'exoneracion_fecha_emision' => 'datetime',
        'exoneracion_tarifa_exonerada' => 'decimal:2',
        'exoneracion_monto' => 'decimal:5',
    ];

    public function lineaDetalle(): BelongsTo
    {
        return $this->belongsTo(FeLineaDetalle::class, 'linea_detalle_id');
    }

    public function getTieneExoneracionAttribute(): bool
    {
        return $this->exoneracion_monto !== null && $this->exoneracion_monto > 0;
    }

    public function getTieneImpuestoEspecificoAttribute(): bool
    {
        return in_array($this->codigo, ['03', '04', '05', '06']);
    }
}
