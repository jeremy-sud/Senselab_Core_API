<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DetalleVenta',
    required: ['venta_id', 'producto_id', 'cantidad', 'precio_unitario', 'subtotal_linea'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'venta_id', type: 'integer', description: 'ID de la venta', example: 234),
        new OA\Property(property: 'producto_id', type: 'integer', description: 'ID del producto', example: 45),
        new OA\Property(property: 'numero_linea', type: 'integer', description: 'Número de línea', example: 1),
        new OA\Property(property: 'cantidad', type: 'number', format: 'decimal', description: 'Cantidad', example: 5.00),
        new OA\Property(property: 'precio_unitario', type: 'number', format: 'decimal', description: 'Precio unitario', example: 2000.00),
        new OA\Property(property: 'subtotal_bruto', type: 'number', format: 'decimal', description: 'Subtotal bruto', example: 10000.00),
        new OA\Property(property: 'porcentaje_descuento', type: 'number', format: 'decimal', description: 'Porcentaje de descuento', example: 5.00),
        new OA\Property(property: 'monto_descuento', type: 'number', format: 'decimal', description: 'Monto de descuento', example: 500.00),
        new OA\Property(property: 'subtotal_con_descuento', type: 'number', format: 'decimal', description: 'Subtotal con descuento', example: 9500.00),
        new OA\Property(property: 'tipo_impuesto_id', type: 'integer', description: 'ID del tipo de impuesto', example: 1, nullable: true),
        new OA\Property(property: 'tasa_impuesto', type: 'number', format: 'decimal', description: 'Tasa de impuesto', example: 13.00),
        new OA\Property(property: 'monto_impuesto', type: 'number', format: 'decimal', description: 'Monto de impuesto', example: 1235.00),
        new OA\Property(property: 'total_linea', type: 'number', format: 'decimal', description: 'Total de la línea', example: 10735.00),
        new OA\Property(property: 'detalle_adicional', type: 'string', description: 'Detalle adicional', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 14:30:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 14:30:00'),
        new OA\Property(
            property: 'producto',
            ref: '#/components/schemas/Producto',
            description: 'Producto de la línea',
            nullable: true
        )
    ]
)]
class DetalleVentaSchema
{
}
