<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UnidadMedida',
    required: ['codigo_dgt', 'nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'codigo_dgt', type: 'string', description: 'Código de la DGT (Dirección General de Tributación)', maxLength: 10, example: 'Unid'),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre de la unidad de medida', maxLength: 255, example: 'Unidad'),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción de la unidad', example: 'Unidad individual', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si la unidad está activa', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si la unidad está eliminada', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-01 08:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00')
    ]
)]
class UnidadMedidaSchema
{
}
