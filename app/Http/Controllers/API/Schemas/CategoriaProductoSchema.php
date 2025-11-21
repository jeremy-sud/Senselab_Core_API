<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CategoriaProducto',
    title: 'Categoría de Producto',
    description: 'Categoría para clasificar productos',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Electrónicos'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Categoría de productos electrónicos')
    ]
)]
class CategoriaProductoSchema
{
}
