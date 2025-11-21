<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TipoCuenta',
    required: ['nombre', 'naturaleza'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre del tipo de cuenta', maxLength: 100, example: 'Activo Corriente'),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción del tipo de cuenta', example: 'Bienes y derechos líquidos en menos de un año', nullable: true),
        new OA\Property(
            property: 'naturaleza',
            type: 'string',
            description: 'Naturaleza contable de la cuenta',
            enum: ['Deudora', 'Acreedora'],
            example: 'Deudora'
        ),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si el tipo está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si el tipo está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-01 08:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00')
    ]
)]
class TipoCuentaSchema
{
}
