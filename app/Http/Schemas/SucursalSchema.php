<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Sucursal',
    required: ['empresa_id', 'nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre de la sucursal', maxLength: 255, example: 'Oficina Central'),
        new OA\Property(property: 'direccion', type: 'string', description: 'Dirección de la sucursal', example: 'San José, Costa Rica', nullable: true),
        new OA\Property(property: 'telefono', type: 'string', description: 'Teléfono de la sucursal', maxLength: 50, example: '+(506)0000-0000', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Email de la sucursal', maxLength: 255, example: 'central@senselab.com', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activa', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminada', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00')
    ]
)]
class SucursalSchema
{
}
