<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AsientoContable',
    required: ['empresa_id', 'numero_asiento', 'fecha_asiento', 'tipo_asiento', 'concepto', 'usuario_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'numero_asiento', type: 'string', description: 'Número correlativo del asiento', maxLength: 50, example: 'ASI-2025-001'),
        new OA\Property(property: 'fecha_asiento', type: 'string', format: 'date', description: 'Fecha del asiento contable', example: '2025-01-15'),
        new OA\Property(
            property: 'tipo_asiento',
            type: 'string',
            description: 'Tipo de asiento contable',
            enum: ['Manual', 'Automático'],
            example: 'Manual'
        ),
        new OA\Property(property: 'origen', type: 'string', description: 'Origen del asiento: Venta, Compra, Pago, Cobro, Ajuste, etc.', maxLength: 100, example: 'Venta', nullable: true),
        new OA\Property(property: 'documento_origen_id', type: 'integer', description: 'ID del documento que genera el asiento', example: 123, nullable: true),
        new OA\Property(property: 'concepto', type: 'string', description: 'Descripción o concepto del asiento', example: 'Registro de ventas del día'),
        new OA\Property(property: 'total_debe', type: 'number', format: 'decimal', description: 'Suma total de débitos', example: 5000.00),
        new OA\Property(property: 'total_haber', type: 'number', format: 'decimal', description: 'Suma total de créditos', example: 5000.00),
        new OA\Property(
            property: 'estado',
            type: 'string',
            description: 'Estado del asiento',
            enum: ['Borrador', 'Confirmado', 'Anulado'],
            example: 'Borrador',
            maxLength: 50
        ),
        new OA\Property(property: 'usuario_id', type: 'integer', description: 'ID del usuario que crea el asiento', example: 5),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si el asiento está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si el asiento está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
        new OA\Property(
            property: 'detalles',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DetalleAsiento'),
            description: 'Líneas de detalle del asiento (debe = haber)'
        )
    ]
)]
class AsientoContableSchema
{
}
