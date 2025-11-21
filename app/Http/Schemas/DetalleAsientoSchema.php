<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DetalleAsiento',
    required: ['asiento_contable_id', 'cuenta_contable_id', 'tipo_movimiento', 'monto'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'asiento_contable_id', type: 'integer', description: 'ID del asiento contable', example: 10),
        new OA\Property(property: 'cuenta_contable_id', type: 'integer', description: 'ID de la cuenta contable', example: 5),
        new OA\Property(
            property: 'tipo_movimiento',
            type: 'string',
            description: 'Tipo de movimiento',
            enum: ['Debe', 'Haber'],
            example: 'Debe',
            maxLength: 10
        ),
        new OA\Property(property: 'monto', type: 'number', format: 'decimal', description: 'Monto del movimiento', example: 15000.00),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción del detalle', example: 'Pago a proveedor', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(
            property: 'cuentaContable',
            ref: '#/components/schemas/CuentaContable',
            description: 'Cuenta contable del detalle',
            nullable: true
        )
    ]
)]
class DetalleAsientoSchema
{
}
