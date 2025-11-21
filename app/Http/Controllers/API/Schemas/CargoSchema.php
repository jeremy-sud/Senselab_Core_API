<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Cargo',
    title: 'Cargo',
    description: 'Cargo o puesto de trabajo',
    required: ['id', 'nombre', 'empresa_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Gerente General'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Responsable de la administración general'),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'activo', type: 'boolean', example: true)
    ]
)]
class CargoSchema
{
}
