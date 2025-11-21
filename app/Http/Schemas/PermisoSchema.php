<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Permiso',
    required: ['nombre', 'slug'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre del permiso', maxLength: 100, example: 'Ver Empresas'),
        new OA\Property(property: 'slug', type: 'string', description: 'Slug del permiso', maxLength: 100, example: 'ver-empresas'),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción del permiso', example: 'Permiso para ver empresas', nullable: true),
        new OA\Property(property: 'modulo', type: 'string', description: 'Módulo al que pertenece', maxLength: 100, example: 'Empresas', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00')
    ]
)]
class PermisoSchema
{
}
