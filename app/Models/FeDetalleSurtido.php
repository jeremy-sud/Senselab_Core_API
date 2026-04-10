<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Modelo para líneas de detalle de surtidos/combos.
 *
 * Soporta hasta 20 items por surtido según spec Hacienda v4.4.
 * Cada item tiene su propio CAByS, cantidad, precio e impuestos.
 * Obligatorio cuando se facturan surtidos con diferentes tarifas IVA.
 *
 * Brecha #31 del análisis comparativo.
 */
class FeDetalleSurtido extends Model
{
    /** @use HasFactory<\Database\Factories\FeDetalleSurtidoFactory> */
    use HasFactory;

    protected $table = 'fe_detalle_surtido';

    protected $fillable = [
        'linea_detalle_id',
        'numero_linea_surtido',
        'codigo_cabys_surtido',
        'cantidad_surtido',
        'unidad_medida_surtido',
        'detalle_surtido',
        'precio_unitario_surtido',
        'monto_total_surtido',
        'monto_descuento_surtido',
        'subtotal_surtido',
    ];

    protected $casts = [
        'numero_linea_surtido' => 'integer',
        'cantidad_surtido' => 'decimal:3',
        'precio_unitario_surtido' => 'decimal:5',
        'monto_total_surtido' => 'decimal:5',
        'monto_descuento_surtido' => 'decimal:5',
        'subtotal_surtido' => 'decimal:5',
    ];

    public function lineaDetalle(): BelongsTo
    {
        return $this->belongsTo(FeLineaDetalle::class, 'linea_detalle_id');
    }

    public function impuestos(): HasMany
    {
        return $this->hasMany(FeSurtidoImpuesto::class, 'detalle_surtido_id');
    }
}
