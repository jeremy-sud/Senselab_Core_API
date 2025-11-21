<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CuentaPorCobrar',
    required: ['empresa_id', 'cliente_id', 'numero_documento', 'fecha_emision', 'fecha_vencimiento', 'monto_original', 'monto_pendiente'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'cliente_id', type: 'integer', description: 'ID del cliente', example: 15),
        new OA\Property(property: 'venta_id', type: 'integer', description: 'ID de la venta asociada', example: 234, nullable: true),
        new OA\Property(property: 'numero_documento', type: 'string', description: 'Número de documento', maxLength: 100, example: 'CXC-2025-001'),
        new OA\Property(property: 'fecha_emision', type: 'string', format: 'date', description: 'Fecha de emisión', example: '2025-01-15'),
        new OA\Property(property: 'fecha_vencimiento', type: 'string', format: 'date', description: 'Fecha de vencimiento', example: '2025-02-15'),
        new OA\Property(property: 'monto_original', type: 'number', format: 'decimal', description: 'Monto original', example: 10000.00),
        new OA\Property(property: 'monto_pendiente', type: 'number', format: 'decimal', description: 'Monto pendiente', example: 7500.00),
        new OA\Property(property: 'monto_pagado', type: 'number', format: 'decimal', description: 'Monto pagado', example: 2500.00),
        new OA\Property(
            property: 'estado',
            type: 'string',
            description: 'Estado de la cuenta',
            enum: ['Pendiente', 'Pagada Parcial', 'Pagada', 'Vencida', 'Anulada'],
            example: 'Pagada Parcial',
            maxLength: 50
        ),
        new OA\Property(property: 'moneda', type: 'string', description: 'Código de moneda', maxLength: 3, example: 'CRC'),
        new OA\Property(property: 'observaciones', type: 'string', description: 'Observaciones', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activa', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminada', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(
            property: 'cliente',
            ref: '#/components/schemas/Cliente',
            description: 'Cliente de la cuenta',
            nullable: true
        )
    ]
)]
class CuentaPorCobrarSchema
{
}
