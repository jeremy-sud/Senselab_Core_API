<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para medios de pago del comprobante electrónico.
 *
 * Soporta hasta 4 medios de pago por comprobante según spec Hacienda v4.4.
 */
class FeMedioPago extends Model
{
    protected $table = 'fe_medios_pago';

    protected $fillable = [
        'comprobante_id',
        'tipo_medio_pago',
        'medio_pago_otros',
        'total_medio_pago',
    ];

    protected $casts = [
        'total_medio_pago' => 'decimal:5',
    ];

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(ComprobanteElectronicoFe::class, 'comprobante_id');
    }
}
