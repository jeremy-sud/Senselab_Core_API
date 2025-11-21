<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Configuracion',
    required: ['empresa_id', 'clave', 'tipo_dato'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'clave', type: 'string', description: 'Clave de configuración', maxLength: 100, example: 'iva_default'),
        new OA\Property(property: 'valor', type: 'string', description: 'Valor de la configuración', example: '13', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción', example: 'IVA predeterminado', nullable: true),
        new OA\Property(property: 'tipo_dato', type: 'string', description: 'Tipo de dato', maxLength: 50, example: 'string'),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00')
    ]
)]
class ConfiguracionSchema
{
}
