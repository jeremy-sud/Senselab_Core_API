<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DetalleEntradaInventario',
    required: ['entrada_inventario_id', 'producto_id', 'numero_linea', 'cantidad', 'costo_unitario', 'subtotal', 'total_linea'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'entrada_inventario_id', type: 'integer', description: 'ID de la entrada de inventario', example: 1),
        new OA\Property(property: 'producto_id', type: 'integer', description: 'ID del producto', example: 15),
        new OA\Property(property: 'numero_linea', type: 'integer', description: 'Número de línea en el documento', example: 1),
        new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', description: 'Cantidad de unidades', example: 50.00),
        new OA\Property(property: 'costo_unitario', type: 'number', format: 'decimal', description: 'Costo unitario del producto', example: 25.50),
        new OA\Property(property: 'subtotal', type: 'number', format: 'decimal', description: 'Subtotal de la línea (cantidad × costo_unitario)', example: 1275.00),
        new OA\Property(property: 'porcentaje_impuesto', type: 'number', format: 'decimal', description: 'Porcentaje de impuesto aplicado', example: 13.00),
        new OA\Property(property: 'monto_impuesto', type: 'number', format: 'decimal', description: 'Monto del impuesto', example: 165.75),
        new OA\Property(property: 'total_linea', type: 'number', format: 'decimal', description: 'Total de la línea (subtotal + impuesto)', example: 1440.75),
        new OA\Property(property: 'lote', type: 'string', description: 'Número de lote del producto', maxLength: 100, example: 'LOTE-2025-A123', nullable: true),
        new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', description: 'Fecha de vencimiento del producto', example: '2026-12-31', nullable: true),
        new OA\Property(property: 'observaciones', type: 'string', description: 'Observaciones de la línea', example: 'Producto sin daños', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si el registro está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si el registro está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
        new OA\Property(
            property: 'producto',
            ref: '#/components/schemas/Producto',
            description: 'Relación con el producto',
            nullable: true
        )
    ]
)]
class DetalleEntradaInventarioSchema
{
}
