<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Marca',
    required: ['nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre de la marca', maxLength: 255, example: 'Samsung'),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción de la marca', example: 'Marca de electrodomésticos y electrónicos', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si la marca está activa', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si la marca está eliminada', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-01 08:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00')
    ]
)]
class MarcaSchema
{
}
