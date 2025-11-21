<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TipoImpuesto',
    required: ['codigo_hacienda', 'nombre', 'comentario'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'codigo_hacienda', type: 'string', description: 'Código de Hacienda de Costa Rica', maxLength: 10, example: '01'),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre del tipo de impuesto', maxLength: 100, example: 'IVA'),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción del impuesto', example: 'Impuesto al Valor Agregado', nullable: true),
        new OA\Property(property: 'comentario', type: 'string', description: 'Comentario adicional', example: 'IVA General'),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si el tipo está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si el tipo está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-01 08:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00')
    ]
)]
class TipoImpuestoSchema
{
}
