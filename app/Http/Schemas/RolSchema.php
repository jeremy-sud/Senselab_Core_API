<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Rol',
    required: ['nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre del rol', maxLength: 100, example: 'Administrador'),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción del rol', example: 'Acceso total al sistema', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(
            property: 'permisos',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/Permiso'),
            description: 'Permisos asignados al rol'
        )
    ]
)]
class RolSchema
{
}
