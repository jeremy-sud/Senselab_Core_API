<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Almacen',
    title: 'Almacén',
    description: 'Almacén o bodega de inventario',
    required: ['id', 'empresa_id', 'sucursal_id', 'nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'sucursal_id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Bodega Principal'),
        new OA\Property(property: 'codigo', type: 'string', nullable: true, example: 'ALM-001'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Almacén de productos terminados'),
        new OA\Property(property: 'ubicacion', type: 'string', nullable: true, example: 'Edificio A, Planta Baja'),
        new OA\Property(property: 'es_principal', type: 'boolean', example: true),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'empresa', ref: '#/components/schemas/Empresa'),
        new OA\Property(property: 'sucursal', ref: '#/components/schemas/Sucursal')
    ]
)]
class AlmacenSchema
{
}
