<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Producto',
    title: 'Producto',
    description: 'Producto del inventario',
    required: ['id', 'nombre', 'codigo', 'empresa_id', 'tipo'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Laptop Dell Inspiron'),
        new OA\Property(property: 'codigo', type: 'string', example: 'PROD-001'),
        new OA\Property(property: 'codigo_barras', type: 'string', nullable: true, example: '1234567890123'),
        new OA\Property(property: 'descripcion', type: 'string', nullable: true, example: 'Laptop 15 pulgadas, 8GB RAM'),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'categoria_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'tipo', type: 'string', enum: ['PRODUCTO', 'SERVICIO', 'COMBO'], example: 'PRODUCTO'),
        new OA\Property(property: 'precio_compra', type: 'number', format: 'decimal', nullable: true, example: 450000.00),
        new OA\Property(property: 'precio_venta', type: 'number', format: 'decimal', example: 650000.00),
        new OA\Property(property: 'unidad_medida_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'marca_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'impuesto_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'empresa', ref: '#/components/schemas/Empresa'),
        new OA\Property(property: 'categoria', ref: '#/components/schemas/CategoriaProducto', nullable: true),
        new OA\Property(property: 'unidad_medida', ref: '#/components/schemas/UnidadMedida', nullable: true),
        new OA\Property(property: 'marca', ref: '#/components/schemas/Marca', nullable: true),
        new OA\Property(property: 'impuesto', ref: '#/components/schemas/Impuesto', nullable: true)
    ]
)]
class ProductoSchema
{
}
