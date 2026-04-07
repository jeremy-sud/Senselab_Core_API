<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para otros cargos del comprobante electrónico.
 *
 * Soporta hasta 15 cargos adicionales por comprobante según spec Hacienda v4.4.
 */
class FeOtroCargo extends Model
{
    protected $table = 'fe_otros_cargos';

    protected $fillable = [
        'comprobante_id',
        'tipo_documento_oc',
        'tipo_documento_otros',
        'tercero_tipo_identificacion',
        'tercero_numero_identificacion',
        'nombre_tercero',
        'detalle',
        'porcentaje_oc',
        'monto_cargo',
    ];

    protected $casts = [
        'porcentaje_oc' => 'decimal:5',
        'monto_cargo' => 'decimal:5',
    ];

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(ComprobanteElectronicoFe::class, 'comprobante_id');
    }
}
