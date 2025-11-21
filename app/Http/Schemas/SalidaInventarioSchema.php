<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SalidaInventario',
    required: ['empresa_id', 'fecha_salida', 'estado', 'monto_total'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'almacen_id', type: 'integer', description: 'ID del almacén', example: 1, nullable: true),
        new OA\Property(property: 'fecha_salida', type: 'string', format: 'date-time', description: 'Fecha y hora de salida del inventario', example: '2025-01-15 14:00:00'),
        new OA\Property(
            property: 'tipo_salida',
            type: 'string',
            description: 'Tipo de movimiento de salida',
            enum: ['Venta', 'Ajuste Negativo', 'Devolución Proveedor', 'Transferencia', 'Consumo Interno'],
            example: 'Venta',
            maxLength: 50,
            nullable: true
        ),
        new OA\Property(property: 'venta_id', type: 'integer', description: 'ID de la venta asociada', example: 12, nullable: true),
        new OA\Property(property: 'cliente_id', type: 'integer', description: 'ID del cliente', example: 8, nullable: true),
        new OA\Property(property: 'proveedor_id', type: 'integer', description: 'ID del proveedor (en caso de devolución)', example: 3, nullable: true),
        new OA\Property(property: 'documento_referencia', type: 'string', description: 'Número de factura o documento de referencia', maxLength: 100, example: 'FACT-V-2025-050', nullable: true),
        new OA\Property(
            property: 'estado',
            type: 'string',
            description: 'Estado del movimiento: Pendiente (creado pero no procesado), Procesada (inventario actualizado), Cancelada',
            enum: ['Pendiente', 'Procesada', 'Cancelada'],
            example: 'Pendiente',
            maxLength: 50
        ),
        new OA\Property(property: 'monto_total', type: 'number', format: 'decimal', description: 'Monto total de la salida (suma de detalles)', example: 850.50),
        new OA\Property(property: 'observaciones', type: 'string', description: 'Observaciones generales', example: 'Entregado al cliente sin novedades', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción adicional de la salida', example: 'Venta directa mostrador', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si el registro está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si el registro está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 14:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 14:00:00'),
        new OA\Property(
            property: 'almacen',
            ref: '#/components/schemas/Almacen',
            description: 'Relación con el almacén',
            nullable: true
        ),
        new OA\Property(
            property: 'cliente',
            ref: '#/components/schemas/Cliente',
            description: 'Relación con el cliente',
            nullable: true
        ),
        new OA\Property(
            property: 'proveedor',
            ref: '#/components/schemas/Proveedor',
            description: 'Relación con el proveedor',
            nullable: true
        ),
        new OA\Property(
            property: 'venta',
            ref: '#/components/schemas/Venta',
            description: 'Relación con la venta',
            nullable: true
        ),
        new OA\Property(
            property: 'detalles',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DetalleSalidaInventario'),
            description: 'Líneas de detalle de la salida'
        )
    ]
)]
class SalidaInventarioSchema
{
}
