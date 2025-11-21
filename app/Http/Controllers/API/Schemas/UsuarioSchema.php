<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Usuario',
    title: 'Usuario',
    description: 'Modelo de Usuario del sistema',
    required: ['id', 'nombre', 'email', 'empresa_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Juan Pérez'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@ursol.com'),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'cargo_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(
            property: 'roles',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Rol')
        ),
        new OA\Property(property: 'empresa', ref: '#/components/schemas/Empresa'),
        new OA\Property(property: 'cargo', ref: '#/components/schemas/Cargo', nullable: true)
    ]
)]
class UsuarioSchema
{
}
