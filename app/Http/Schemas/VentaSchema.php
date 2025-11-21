<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Venta',
    required: ['empresa_id', 'sucursal_id', 'usuario_id', 'fecha_venta', 'monto_total_venta', 'estado_venta', 'forma_pago_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'sucursal_id', type: 'integer', description: 'ID de la sucursal', example: 1),
        new OA\Property(property: 'cliente_id', type: 'integer', description: 'ID del cliente', example: 15, nullable: true),
        new OA\Property(property: 'usuario_id', type: 'integer', description: 'ID del usuario que registra', example: 3),
        new OA\Property(property: 'fecha_venta', type: 'string', format: 'date-time', description: 'Fecha y hora de la venta', example: '2025-01-15 14:30:00'),
        new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', description: 'Fecha de vencimiento (crédito)', example: '2025-02-15', nullable: true),
        new OA\Property(property: 'tipo_comprobante', type: 'string', description: 'Tipo de comprobante electrónico', maxLength: 50, example: '01', nullable: true),
        new OA\Property(property: 'numero_comprobante', type: 'string', description: 'Número del comprobante', maxLength: 50, example: 'FE-001-00001234', nullable: true),
        new OA\Property(property: 'clave_numerica_hacienda', type: 'string', description: 'Clave numérica de Hacienda', maxLength: 50, nullable: true),
        new OA\Property(property: 'moneda', type: 'string', description: 'Código de moneda', maxLength: 3, example: 'CRC'),
        new OA\Property(property: 'subtotal_bruto_total', type: 'number', format: 'decimal', description: 'Subtotal bruto', example: 10000.00),
        new OA\Property(property: 'monto_descuento_total', type: 'number', format: 'decimal', description: 'Monto de descuento', example: 500.00),
        new OA\Property(property: 'subtotal_neto_total', type: 'number', format: 'decimal', description: 'Subtotal neto', example: 9500.00),
        new OA\Property(property: 'monto_impuesto_total', type: 'number', format: 'decimal', description: 'Monto de impuesto', example: 1235.00),
        new OA\Property(property: 'monto_total_venta', type: 'number', format: 'decimal', description: 'Total de la venta', example: 10735.00),
        new OA\Property(property: 'estado_venta', type: 'string', description: 'Estado de la venta', maxLength: 50, example: 'Pendiente'),
        new OA\Property(property: 'condicion_pago', type: 'string', description: 'Condición de pago', maxLength: 100, example: 'Contado', nullable: true),
        new OA\Property(property: 'plazo_credito_dias', type: 'integer', description: 'Plazo de crédito en días', example: 30),
        new OA\Property(property: 'estado_hacienda', type: 'string', description: 'Estado en Hacienda', maxLength: 20, example: 'Aceptado'),
        new OA\Property(property: 'forma_pago_id', type: 'integer', description: 'ID de la forma de pago', example: 1),
        new OA\Property(property: 'observaciones', type: 'string', description: 'Observaciones', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 14:30:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 14:30:00'),
        new OA\Property(
            property: 'cliente',
            ref: '#/components/schemas/Cliente',
            description: 'Cliente de la venta',
            nullable: true
        ),
        new OA\Property(
            property: 'detalles',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DetalleVenta'),
            description: 'Líneas de detalle de la venta'
        )
    ]
)]
class VentaSchema
{
}
