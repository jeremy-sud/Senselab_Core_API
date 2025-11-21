<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrdenCompra',
    required: ['empresa_id', 'proveedor_id', 'usuario_id', 'numero_orden', 'fecha_orden', 'total_orden'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'proveedor_id', type: 'integer', description: 'ID del proveedor', example: 3),
        new OA\Property(property: 'usuario_id', type: 'integer', description: 'ID del usuario que crea la orden', example: 2),
        new OA\Property(property: 'numero_orden', type: 'string', description: 'Número de la orden', maxLength: 50, example: 'OC-2025-001'),
        new OA\Property(property: 'fecha_orden', type: 'string', format: 'date', description: 'Fecha de la orden', example: '2025-01-15'),
        new OA\Property(property: 'fecha_entrega_esperada', type: 'string', format: 'date', description: 'Fecha de entrega esperada', example: '2025-01-20', nullable: true),
        new OA\Property(property: 'moneda', type: 'string', description: 'Código de moneda', maxLength: 3, example: 'CRC'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'decimal', description: 'Subtotal de la orden', example: 50000.00),
        new OA\Property(property: 'impuesto_total', type: 'number', format: 'decimal', description: 'Impuesto total', example: 6500.00),
        new OA\Property(property: 'total_orden', type: 'number', format: 'decimal', description: 'Total de la orden', example: 56500.00),
        new OA\Property(property: 'estado', type: 'string', description: 'Estado de la orden', maxLength: 50, example: 'Pendiente'),
        new OA\Property(property: 'observaciones', type: 'string', description: 'Observaciones', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activa', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminada', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(
            property: 'proveedor',
            ref: '#/components/schemas/Proveedor',
            description: 'Proveedor de la orden',
            nullable: true
        )
    ]
)]
class OrdenCompraSchema
{
}
