<?php

namespace App\DTOs\Transformers;

use App\Models\Producto;

/**
 * Transformer para convertir Producto a array de respuesta
 *
 * Fecha de creación: 12 de febrero de 2026
 */
class ProductoTransformer
{
    /**
     * Transformar un Producto a array
     */
    public static function transform(Producto $producto): array
    {
        return [
            'id' => $producto->id,
            'nombre' => $producto->nombre,
            'descripcion' => $producto->descripcion,
            'precio' => (float) $producto->precio,
            'precio_costo' => (float) $producto->precio_costo,
            'stock' => $producto->stock_actual ?? 0,
            'categoria_id' => $producto->categoria_id,
            'categoria' => $producto->categoria?->nombre,
            'sku' => $producto->sku,
            'codigo_interno' => $producto->codigo_interno,
            'activo' => (bool) $producto->activo,
            'unidad_medida' => $producto->unidad_medida,
            'created_at' => $producto->created_at?->toIso8601String(),
            'updated_at' => $producto->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Transformar colección de Productos a array
     */
    public static function collection(iterable $productos): array
    {
        $result = [];
        foreach ($productos as $producto) {
            $result[] = self::transform($producto);
        }
        return $result;
    }
}
