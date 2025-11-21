<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Almacen',
    required: ['empresa_id', 'nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'sucursal_id', type: 'integer', description: 'ID de la sucursal', example: 1, nullable: true),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre del almacén', maxLength: 255, example: 'Almacén Central'),
        new OA\Property(property: 'codigo', type: 'string', description: 'Código único del almacén', maxLength: 50, example: 'ALM-001', nullable: true),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción del almacén', example: 'Almacén principal de la empresa', nullable: true),
        new OA\Property(property: 'ubicacion', type: 'string', description: 'Ubicación física del almacén', example: 'Bodega 1, Zona Industrial', nullable: true),
        new OA\Property(property: 'responsable_id', type: 'integer', description: 'ID del empleado responsable', example: 5, nullable: true),
        new OA\Property(property: 'es_principal', type: 'boolean', description: 'Indica si es el almacén principal', example: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si el almacén está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si el almacén está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-01 08:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00')
    ]
)]
class AlmacenSchema
{
}
