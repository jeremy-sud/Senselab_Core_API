<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para códigos comerciales por línea de detalle.
 *
 * Soporta hasta 5 códigos comerciales por línea según spec Hacienda v4.4.
 * Cada código tiene un tipo (01=Interno, 02=Proveedor, 03=EAN/UPC, 04=Estándar, 99=Otro)
 * y un valor de hasta 20 caracteres.
 *
 * Brecha #33 del análisis comparativo.
 */
class FeCodigoComercial extends Model
{
    /** @use HasFactory<\Database\Factories\FeCodigoComercialFactory> */
    use HasFactory;

    protected $table = 'fe_codigo_comercial';

    protected $fillable = [
        'linea_detalle_id',
        'orden',
        'tipo',
        'codigo',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function lineaDetalle(): BelongsTo
    {
        return $this->belongsTo(FeLineaDetalle::class, 'linea_detalle_id');
    }
}
