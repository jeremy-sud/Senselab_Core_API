<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TasaImpuesto',
    required: ['tipo_impuesto_id', 'tasa_porcentaje', 'fecha_inicio_vigencia'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'tipo_impuesto_id', type: 'integer', description: 'ID del tipo de impuesto', example: 1),
        new OA\Property(property: 'tasa_porcentaje', type: 'number', format: 'decimal', description: 'Porcentaje de la tasa', example: 13.00),
        new OA\Property(property: 'fecha_inicio_vigencia', type: 'string', format: 'date', description: 'Fecha de inicio de vigencia', example: '2025-01-01'),
        new OA\Property(property: 'fecha_fin_vigencia', type: 'string', format: 'date', description: 'Fecha de fin de vigencia', example: '2025-12-31', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción de la tasa', maxLength: 255, example: 'IVA tarifa general 13%', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si la tasa está activa', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si la tasa está eliminada', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-01 08:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
        new OA\Property(
            property: 'tipoImpuesto',
            ref: '#/components/schemas/TipoImpuesto',
            description: 'Relación con el tipo de impuesto',
            nullable: true
        )
    ]
)]
class TasaImpuestoSchema
{
}
