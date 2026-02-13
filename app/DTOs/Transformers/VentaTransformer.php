<?php

namespace App\DTOs\Transformers;

use App\Models\Venta;

/**
 * Transformer para convertir Venta a array de respuesta
 * 
 * Fecha de creación: 12 de febrero de 2026
 */
class VentaTransformer
{
    /**
     * Transformar una Venta a array
     */
    public static function transform(Venta $venta): array
    {
        return [
            'id' => $venta->id,
            'numero_comprobante' => $venta->numero_comprobante,
            'cliente_id' => $venta->cliente_id,
            'cliente' => $venta->cliente?->nombre,
            'fecha' => $venta->fecha->format('Y-m-d'),
            'subtotal' => (float) $venta->subtotal,
            'impuesto' => (float) $venta->impuesto,
            'total' => (float) $venta->total,
            'estado' => $venta->estado,
            'forma_pago_id' => $venta->forma_pago_id,
            'observaciones' => $venta->observaciones,
            'cantidad_detalles' => $venta->detalles()?->count() ?? 0,
            'created_at' => $venta->created_at?->toIso8601String(),
            'updated_at' => $venta->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Transformar colección de Ventas a array
     */
    public static function collection(iterable $ventas): array
    {
        $result = [];
        foreach ($ventas as $venta) {
            $result[] = self::transform($venta);
        }
        return $result;
    }
}
