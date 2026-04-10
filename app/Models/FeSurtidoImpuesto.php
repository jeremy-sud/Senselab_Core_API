<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo para impuestos por línea de surtido.
 *
 * Soporta hasta 1000 impuestos por línea de surtido según spec Hacienda v4.4.
 * Estructura similar a FeLineaImpuesto pero asociada a fe_detalle_surtido.
 *
 * Brecha #31 del análisis comparativo.
 */
class FeSurtidoImpuesto extends Model
{
    /** @use HasFactory<\Database\Factories\FeSurtidoImpuestoFactory> */
    use HasFactory;

    protected $table = 'fe_surtido_impuesto';

    protected $fillable = [
        'detalle_surtido_id',
        'codigo',
        'codigo_tarifa_iva',
        'tarifa',
        'monto',
        'monto_exportacion',
    ];

    protected $casts = [
        'tarifa' => 'decimal:2',
        'monto' => 'decimal:5',
        'monto_exportacion' => 'decimal:5',
    ];

    public function detalleSurtido(): BelongsTo
    {
        return $this->belongsTo(FeDetalleSurtido::class, 'detalle_surtido_id');
    }
}
