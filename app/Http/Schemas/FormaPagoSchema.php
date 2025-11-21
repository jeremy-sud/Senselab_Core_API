<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FormaPago',
    required: ['codigo_dgt', 'nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'codigo_dgt', type: 'string', description: 'Código DGT para Hacienda', maxLength: 10, example: '01'),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre de la forma de pago', maxLength: 255, example: 'Efectivo'),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción', example: 'Pago en efectivo', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activa', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminada', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00')
    ]
)]
class FormaPagoSchema
{
}
