<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EntradaInventario',
    required: ['empresa_id', 'fecha_entrada', 'estado', 'monto_total'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'almacen_id', type: 'integer', description: 'ID del almacén', example: 1, nullable: true),
        new OA\Property(property: 'fecha_entrada', type: 'string', format: 'date-time', description: 'Fecha y hora de entrada al inventario', example: '2025-01-15 10:30:00'),
        new OA\Property(
            property: 'tipo_entrada',
            type: 'string',
            description: 'Tipo de movimiento de entrada',
            enum: ['Compra', 'Ajuste Positivo', 'Transferencia', 'Devolución Cliente', 'Producción'],
            example: 'Compra',
            maxLength: 50,
            nullable: true
        ),
        new OA\Property(property: 'orden_compra_id', type: 'integer', description: 'ID de la orden de compra asociada', example: 5, nullable: true),
        new OA\Property(property: 'proveedor_id', type: 'integer', description: 'ID del proveedor', example: 3, nullable: true),
        new OA\Property(property: 'documento_referencia', type: 'string', description: 'Número de factura o documento de referencia', maxLength: 100, example: 'FACT-2025-001', nullable: true),
        new OA\Property(
            property: 'estado',
            type: 'string',
            description: 'Estado del movimiento: Pendiente (creado pero no procesado), Procesada (inventario actualizado), Cancelada',
            enum: ['Pendiente', 'Procesada', 'Cancelada'],
            example: 'Pendiente',
            maxLength: 50
        ),
        new OA\Property(property: 'monto_total', type: 'number', format: 'decimal', description: 'Monto total de la entrada (suma de detalles)', example: 1250.75),
        new OA\Property(property: 'observaciones', type: 'string', description: 'Observaciones generales', example: 'Recepción completa sin novedades', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción adicional de la entrada', example: 'Compra semanal de productos', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si el registro está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si el registro está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
        new OA\Property(
            property: 'almacen',
            ref: '#/components/schemas/Almacen',
            description: 'Relación con el almacén',
            nullable: true
        ),
        new OA\Property(
            property: 'proveedor',
            ref: '#/components/schemas/Proveedor',
            description: 'Relación con el proveedor',
            nullable: true
        ),
        new OA\Property(
            property: 'ordenCompra',
            ref: '#/components/schemas/OrdenCompra',
            description: 'Relación con la orden de compra',
            nullable: true
        ),
        new OA\Property(
            property: 'detalles',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DetalleEntradaInventario'),
            description: 'Líneas de detalle de la entrada'
        )
    ]
)]
class EntradaInventarioSchema
{
}
