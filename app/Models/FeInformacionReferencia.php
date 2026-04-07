<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para información de referencia del comprobante electrónico.
 *
 * Soporta hasta 10 referencias por comprobante según spec Hacienda v4.4.
 * Obligatorio en NC, ND, REP y FEC.
 */
class FeInformacionReferencia extends Model
{
    protected $table = 'fe_informacion_referencia';

    protected $fillable = [
        'comprobante_id',
        'tipo_doc',
        'tipo_doc_otro',
        'numero',
        'fecha_emision',
        'codigo',
        'codigo_referencia_otro',
        'razon',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
    ];

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(ComprobanteElectronicoFe::class, 'comprobante_id');
    }
}
