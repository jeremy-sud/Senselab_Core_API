<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DetalleSalidaInventario',
    required: ['salida_inventario_id', 'producto_id', 'numero_linea', 'cantidad', 'costo_unitario', 'total_linea'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'salida_inventario_id', type: 'integer', description: 'ID de la salida de inventario', example: 1),
        new OA\Property(property: 'producto_id', type: 'integer', description: 'ID del producto', example: 15),
        new OA\Property(property: 'numero_linea', type: 'integer', description: 'Número de línea en el documento', example: 1),
        new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', description: 'Cantidad de unidades', example: 25.00),
        new OA\Property(property: 'costo_unitario', type: 'number', format: 'decimal', description: 'Costo unitario del producto al momento de la salida', example: 30.00),
        new OA\Property(property: 'total_linea', type: 'number', format: 'decimal', description: 'Total de la línea (cantidad × costo_unitario)', example: 750.00),
        new OA\Property(property: 'lote', type: 'string', description: 'Número de lote del producto', maxLength: 100, example: 'LOTE-2025-A123', nullable: true),
        new OA\Property(property: 'observaciones', type: 'string', description: 'Observaciones de la línea', example: 'Producto en buen estado', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si el registro está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si el registro está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 14:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 14:00:00'),
        new OA\Property(
            property: 'producto',
            ref: '#/components/schemas/Producto',
            description: 'Relación con el producto',
            nullable: true
        )
    ]
)]
class DetalleSalidaInventarioSchema
{
}
