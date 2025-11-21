<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Rol',
    title: 'Rol',
    description: 'Rol de usuario con permisos',
    required: ['id', 'nombre', 'slug'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Administrador'),
        new OA\Property(property: 'slug', type: 'string', example: 'administrador'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Acceso completo al sistema'),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(
            property: 'permisos',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Permiso')
        )
    ]
)]
class RolSchema
{
}
