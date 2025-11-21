<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'InventarioProducto',
    required: ['almacen_id', 'producto_id', 'stock_actual', 'costo_promedio'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'almacen_id', type: 'integer', description: 'ID del almacén', example: 1),
        new OA\Property(property: 'producto_id', type: 'integer', description: 'ID del producto', example: 45),
        new OA\Property(property: 'stock_actual', type: 'number', format: 'decimal', description: 'Stock actual', example: 150.00),
        new OA\Property(property: 'costo_promedio', type: 'number', format: 'decimal', description: 'Costo promedio', example: 2500.00),
        new OA\Property(property: 'stock_minimo', type: 'number', format: 'decimal', description: 'Stock mínimo', example: 10.00),
        new OA\Property(property: 'stock_maximo', type: 'number', format: 'decimal', description: 'Stock máximo', example: 500.00),
        new OA\Property(property: 'ubicacion', type: 'string', description: 'Ubicación en el almacén', maxLength: 100, example: 'E-3-B', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(
            property: 'producto',
            ref: '#/components/schemas/Producto',
            description: 'Producto del inventario',
            nullable: true
        )
    ]
)]
class InventarioProductoSchema
{
}
