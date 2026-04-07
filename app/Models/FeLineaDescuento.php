<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para descuentos por línea de detalle.
 *
 * Soporta hasta 5 descuentos secuenciales por línea según spec Hacienda v4.4.
 * Cada descuento se calcula sobre la base menos los descuentos anteriores.
 */
class FeLineaDescuento extends Model
{
    protected $table = 'fe_linea_descuentos';

    protected $fillable = [
        'linea_detalle_id',
        'orden',
        'monto_descuento',
        'codigo_descuento',
        'codigo_descuento_otro',
        'naturaleza_descuento',
    ];

    protected $casts = [
        'orden' => 'integer',
        'monto_descuento' => 'decimal:5',
    ];

    public function lineaDetalle(): BelongsTo
    {
        return $this->belongsTo(FeLineaDetalle::class, 'linea_detalle_id');
    }
}
