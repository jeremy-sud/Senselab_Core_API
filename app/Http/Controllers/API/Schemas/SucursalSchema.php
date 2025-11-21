<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Sucursal',
    title: 'Sucursal',
    description: 'Sucursal de una empresa',
    required: ['id', 'empresa_id', 'nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Sucursal Centro'),
        new OA\Property(property: 'codigo', type: 'string', nullable: true, example: 'SUC-001'),
        new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '2222-3333'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'centro@empresa.com'),
        new OA\Property(property: 'direccion', type: 'string', nullable: true, example: 'San José, Centro, Avenida Central'),
        new OA\Property(property: 'provincia', type: 'string', nullable: true, example: 'San José'),
        new OA\Property(property: 'canton', type: 'string', nullable: true, example: 'San José'),
        new OA\Property(property: 'distrito', type: 'string', nullable: true, example: 'Carmen'),
        new OA\Property(property: 'es_principal', type: 'boolean', example: true),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'empresa', ref: '#/components/schemas/Empresa')
    ]
)]
class SucursalSchema
{
}
