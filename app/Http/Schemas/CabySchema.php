<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Caby',
    required: ['codigo', 'descripcion'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'codigo', type: 'string', description: 'Código CABYS', maxLength: 20, example: '8523300201000'),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción del código CABYS', example: 'Equipo de cómputo'),
        new OA\Property(property: 'impuesto_iva_predeterminado', type: 'number', format: 'decimal', description: 'IVA predeterminado', example: 13.00),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00')
    ]
)]
class CabySchema
{
}
