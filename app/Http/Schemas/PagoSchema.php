<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Pago',
    required: ['cuenta_por_cobrar_id', 'forma_pago_id', 'fecha_pago', 'monto_pago'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'cuenta_por_cobrar_id', type: 'integer', description: 'ID de la cuenta por cobrar', example: 15),
        new OA\Property(property: 'forma_pago_id', type: 'integer', description: 'ID de la forma de pago', example: 1),
        new OA\Property(property: 'fecha_pago', type: 'string', format: 'date', description: 'Fecha del pago', example: '2025-01-20'),
        new OA\Property(property: 'monto_pago', type: 'number', format: 'decimal', description: 'Monto del pago', example: 5000.00),
        new OA\Property(property: 'numero_referencia', type: 'string', description: 'Número de referencia', maxLength: 100, example: 'TRANS-12345', nullable: true),
        new OA\Property(property: 'moneda', type: 'string', description: 'Código de moneda', maxLength: 3, example: 'CRC'),
        new OA\Property(property: 'observaciones', type: 'string', description: 'Observaciones', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-20 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-20 10:00:00')
    ]
)]
class PagoSchema
{
}
