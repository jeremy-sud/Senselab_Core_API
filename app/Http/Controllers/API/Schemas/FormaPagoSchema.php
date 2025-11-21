<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FormaPago',
    title: 'Forma de Pago',
    description: 'Método de pago disponible en el sistema',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'codigo_dgt', type: 'string', maxLength: 10, example: '01', description: 'Código DGT de Costa Rica'),
        new OA\Property(property: 'nombre', type: 'string', maxLength: 255, example: 'Efectivo', description: 'Nombre de la forma de pago'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Pago en efectivo al momento de la venta'),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z')
    ]
)]
class FormaPagoSchema
{
}
