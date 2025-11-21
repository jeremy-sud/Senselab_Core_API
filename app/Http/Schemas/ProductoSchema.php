<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Producto',
    required: ['empresa_id', 'codigo', 'codigo_barras', 'nombre', 'tipo_producto'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'categoria_id', type: 'integer', description: 'ID de la categoría', example: 5, nullable: true),
        new OA\Property(property: 'codigo', type: 'string', description: 'Código interno del producto', maxLength: 100, example: 'PROD-001'),
        new OA\Property(property: 'codigo_barras', type: 'string', description: 'Código de barras', example: '7501234567890'),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre del producto', maxLength: 255, example: 'Laptop HP 15-dw3000'),
        new OA\Property(property: 'descripcion', type: 'string', description: 'Descripción detallada', example: 'Laptop con procesador Intel i5, 8GB RAM, 256GB SSD', nullable: true),
        new OA\Property(property: 'unidad_medida_id', type: 'integer', description: 'ID de la unidad de medida', example: 1, nullable: true),
        new OA\Property(property: 'marca_id', type: 'integer', description: 'ID de la marca', example: 3, nullable: true),
        new OA\Property(property: 'proveedor_id', type: 'integer', description: 'ID del proveedor principal', example: 7, nullable: true),
        new OA\Property(property: 'tipo_impuesto_id', type: 'integer', description: 'ID del tipo de impuesto', example: 1, nullable: true),
        new OA\Property(property: 'cabys_id', type: 'integer', description: 'ID del código CAByS', example: 12, nullable: true),
        new OA\Property(property: 'precio_compra', type: 'number', format: 'decimal', description: 'Precio de compra', example: 450.00),
        new OA\Property(property: 'precio_venta', type: 'number', format: 'decimal', description: 'Precio de venta', example: 650.00),
        new OA\Property(property: 'stock_minimo', type: 'number', format: 'decimal', description: 'Stock mínimo', example: 5.00),
        new OA\Property(property: 'stock_maximo', type: 'number', format: 'decimal', description: 'Stock máximo', example: 50.00),
        new OA\Property(
            property: 'tipo_producto',
            type: 'string',
            description: 'Tipo de producto',
            enum: ['Producto', 'Servicio'],
            example: 'Producto'
        ),
        new OA\Property(property: 'vende', type: 'boolean', description: 'Indica si se vende', example: true),
        new OA\Property(property: 'compra', type: 'boolean', description: 'Indica si se compra', example: true),
        new OA\Property(property: 'controla_inventario', type: 'boolean', description: 'Indica si controla inventario', example: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-01 08:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00'),
        new OA\Property(
            property: 'categoria',
            ref: '#/components/schemas/CategoriaProducto',
            description: 'Categoría del producto',
            nullable: true
        ),
        new OA\Property(
            property: 'unidadMedida',
            ref: '#/components/schemas/UnidadMedida',
            description: 'Unidad de medida',
            nullable: true
        ),
        new OA\Property(
            property: 'marca',
            ref: '#/components/schemas/Marca',
            description: 'Marca del producto',
            nullable: true
        )
    ]
)]
class ProductoSchema
{
}
