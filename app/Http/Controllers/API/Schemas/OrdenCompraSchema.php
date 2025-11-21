<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'OrdenCompra',
    title: 'Orden de Compra',
    description: 'Orden de compra a proveedor',
    required: ['id', 'empresa_id', 'proveedor_id', 'numero_orden', 'fecha_orden'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'proveedor_id', type: 'integer', example: 5),
        new OA\Property(property: 'usuario_id', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'numero_orden', type: 'string', example: 'OC-000001'),
        new OA\Property(property: 'fecha_orden', type: 'string', format: 'date', example: '2024-01-15'),
        new OA\Property(property: 'fecha_entrega_esperada', type: 'string', format: 'date', nullable: true, example: '2024-01-30'),
        new OA\Property(property: 'estado', type: 'string', enum: ['borrador', 'enviada', 'confirmada', 'recibida_parcial', 'recibida_completa', 'cancelada'], example: 'enviada'),
        new OA\Property(property: 'subtotal', type: 'number', format: 'decimal', example: 450000.00),
        new OA\Property(property: 'impuesto_total', type: 'number', format: 'decimal', example: 58500.00),
        new OA\Property(property: 'total_orden', type: 'number', format: 'decimal', example: 508500.00),
        new OA\Property(property: 'moneda', type: 'string', enum: ['CRC', 'USD'], example: 'CRC'),
        new OA\Property(property: 'observaciones', type: 'string', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time'),
        new OA\Property(property: 'proveedor', ref: '#/components/schemas/Proveedor'),
        new OA\Property(property: 'empresa', ref: '#/components/schemas/Empresa')
    ]
)]
class OrdenCompraSchema
{
}
