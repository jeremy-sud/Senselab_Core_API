<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UnidadMedida',
    title: 'Unidad de Medida',
    description: 'Unidad de medida para productos',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Unidad'),
        new OA\Property(property: 'abreviatura', type: 'string', example: 'Und')
    ]
)]
class UnidadMedidaSchema
{
}
