<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Impuesto',
    title: 'Impuesto',
    description: 'Impuesto aplicable a productos',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'IVA 13%'),
        new OA\Property(property: 'porcentaje', type: 'number', format: 'decimal', example: 13.00)
    ]
)]
class ImpuestoSchema
{
}
