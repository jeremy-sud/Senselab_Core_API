<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CuentaContable',
    required: ['empresa_id', 'nombre', 'codigo'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre de la cuenta contable', maxLength: 255, example: 'Caja General'),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción de la cuenta', example: 'Efectivo en caja principal', nullable: true),
        new OA\Property(property: 'codigo', type: 'string', description: 'Código de la cuenta', maxLength: 50, example: '1.1.01'),
        new OA\Property(property: 'tipo_cuenta_id', type: 'integer', description: 'ID del tipo de cuenta', example: 1, nullable: true),
        new OA\Property(property: 'cuenta_padre_id', type: 'integer', description: 'ID de la cuenta padre (estructura jerárquica)', example: 5, nullable: true),
        new OA\Property(property: 'permite_movimientos', type: 'boolean', description: 'Indica si permite registrar movimientos directamente', example: true),
        new OA\Property(property: 'saldo_actual', type: 'number', format: 'decimal', description: 'Saldo actual de la cuenta', example: 15000.00),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si la cuenta está activa', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si la cuenta está eliminada', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-01 08:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
        new OA\Property(
            property: 'tipoCuenta',
            ref: '#/components/schemas/TipoCuenta',
            description: 'Relación con el tipo de cuenta',
            nullable: true
        ),
        new OA\Property(
            property: 'cuentaPadre',
            ref: '#/components/schemas/CuentaContable',
            description: 'Cuenta padre en la jerarquía',
            nullable: true
        ),
        new OA\Property(
            property: 'subCuentas',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CuentaContable'),
            description: 'Cuentas hijas en la jerarquía'
        )
    ]
)]
class CuentaContableSchema
{
}
