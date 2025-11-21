<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Venta',
    title: 'Venta',
    description: 'Venta o factura del sistema',
    required: ['id', 'empresa_id', 'cliente_id', 'fecha_venta', 'tipo_comprobante', 'monto_total_venta'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'sucursal_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'cliente_id', type: 'integer', example: 5),
        new OA\Property(property: 'usuario_id', type: 'integer', nullable: true, example: 3),
        new OA\Property(property: 'fecha_venta', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000000Z'),
        new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', nullable: true, example: '2024-02-15'),
        new OA\Property(property: 'tipo_comprobante', type: 'string', enum: ['factura', 'tiquete', 'nota_credito', 'nota_debito'], example: 'factura'),
        new OA\Property(property: 'serie_comprobante', type: 'string', nullable: true, example: '001'),
        new OA\Property(property: 'numero_comprobante', type: 'string', example: 'FAC-00000123'),
        new OA\Property(property: 'clave_numerica_hacienda', type: 'string', nullable: true, example: '50610012345678901234567890123456789012345678'),
        new OA\Property(property: 'consecutivo_hacienda', type: 'string', nullable: true, example: '00100001010000000123'),
        new OA\Property(property: 'moneda', type: 'string', enum: ['CRC', 'USD'], example: 'CRC'),
        new OA\Property(property: 'subtotal_bruto_total', type: 'number', format: 'decimal', example: 100000.00),
        new OA\Property(property: 'monto_descuento_total', type: 'number', format: 'decimal', example: 5000.00),
        new OA\Property(property: 'subtotal_neto_total', type: 'number', format: 'decimal', example: 95000.00),
        new OA\Property(property: 'monto_impuesto_total', type: 'number', format: 'decimal', example: 12350.00),
        new OA\Property(property: 'monto_total_venta', type: 'number', format: 'decimal', example: 107350.00),
        new OA\Property(property: 'estado_venta', type: 'string', enum: ['pendiente', 'pagada', 'parcial', 'anulada'], example: 'pendiente'),
        new OA\Property(property: 'condicion_pago', type: 'string', enum: ['contado', 'credito'], example: 'contado'),
        new OA\Property(property: 'condicion_venta_dgt', type: 'string', nullable: true, example: '01'),
        new OA\Property(property: 'plazo_credito_dias', type: 'integer', nullable: true, example: 30),
        new OA\Property(property: 'observaciones', type: 'string', nullable: true, example: 'Venta con descuento especial'),
        new OA\Property(property: 'estado_hacienda', type: 'string', nullable: true, enum: ['pendiente', 'aceptado', 'rechazado'], example: 'aceptado'),
        new OA\Property(property: 'forma_pago_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000000Z'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000000Z'),
        new OA\Property(property: 'empresa', ref: '#/components/schemas/Empresa'),
        new OA\Property(property: 'cliente', ref: '#/components/schemas/Cliente')
    ]
)]
class VentaSchema
{
}
