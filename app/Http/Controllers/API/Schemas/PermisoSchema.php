<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Permiso',
    title: 'Permiso',
    description: 'Permiso del sistema',
    required: ['id', 'nombre', 'slug', 'modulo'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Ver productos'),
        new OA\Property(property: 'slug', type: 'string', example: 'productos.ver'),
        new OA\Property(property: 'modulo', type: 'string', example: 'productos'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Permite visualizar el listado de productos')
    ]
)]
class PermisoSchema
{
}
