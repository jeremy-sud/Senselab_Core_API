<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CuentaPorPagar',
    title: 'Cuenta por Pagar',
    description: 'Cuenta por pagar a proveedor',
    required: ['id', 'empresa_id', 'proveedor_id', 'monto_total'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'proveedor_id', type: 'integer', example: 8),
        new OA\Property(property: 'orden_compra_id', type: 'integer', nullable: true, example: 12),
        new OA\Property(property: 'numero_documento', type: 'string', example: 'CXP-00001'),
        new OA\Property(property: 'fecha_emision', type: 'string', format: 'date', example: '2024-01-15'),
        new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', example: '2024-03-15'),
        new OA\Property(property: 'fecha_recepcion_documento', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'monto_total', type: 'number', format: 'decimal', example: 500000.00),
        new OA\Property(property: 'monto_pagado', type: 'number', format: 'decimal', example: 200000.00),
        new OA\Property(property: 'monto_pendiente', type: 'number', format: 'decimal', example: 300000.00),
        new OA\Property(property: 'estado', type: 'string', enum: ['Pendiente', 'Pagada Parcialmente', 'Pagada', 'Vencida', 'Cancelada'], example: 'Pendiente'),
        new OA\Property(property: 'moneda', type: 'string', enum: ['CRC', 'USD'], example: 'CRC'),
        new OA\Property(property: 'observaciones', type: 'string', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time')
    ]
)]
class CuentaPorPagarSchema
{
}
