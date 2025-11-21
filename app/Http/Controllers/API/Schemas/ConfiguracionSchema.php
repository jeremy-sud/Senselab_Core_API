<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Configuracion',
    title: 'Configuración',
    description: 'Configuración clave-valor del sistema por empresa',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'clave', type: 'string', maxLength: 255, example: 'moneda_default', description: 'Identificador único de la configuración'),
        new OA\Property(property: 'valor', type: 'string', example: 'CRC', description: 'Valor almacenado como string'),
        new OA\Property(
            property: 'tipo_dato',
            type: 'string',
            enum: ['string', 'integer', 'float', 'boolean', 'json', 'array'],
            example: 'string',
            description: 'Tipo de dato del valor'
        ),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Moneda por defecto del sistema'),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00Z'),
        new OA\Property(
            property: 'empresa',
            ref: '#/components/schemas/Empresa',
            description: 'Empresa a la que pertenece'
        )
    ]
)]
class ConfiguracionSchema
{
}
