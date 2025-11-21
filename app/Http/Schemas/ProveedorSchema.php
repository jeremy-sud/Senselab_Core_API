<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Proveedor',
    required: ['empresa_id', 'tipo_identificacion', 'numero_identificacion', 'nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'tipo_identificacion', type: 'string', description: 'Tipo de identificación', maxLength: 10, example: '02'),
        new OA\Property(property: 'numero_identificacion', type: 'string', description: 'Número de identificación', maxLength: 50, example: '3-101-234567'),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre o razón social', maxLength: 255, example: 'Distribuidora XYZ S.A.'),
        new OA\Property(property: 'nombre_comercial', type: 'string', description: 'Nombre comercial', maxLength: 255, example: 'Distri XYZ', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Correo electrónico', maxLength: 255, example: 'ventas@distrixyz.com', nullable: true),
        new OA\Property(property: 'telefono', type: 'string', description: 'Número de teléfono', maxLength: 50, example: '2222-4444', nullable: true),
        new OA\Property(property: 'direccion', type: 'string', description: 'Dirección física', example: 'Heredia, Zona Franca', nullable: true),
        new OA\Property(property: 'provincia', type: 'string', description: 'Provincia', maxLength: 100, example: 'Heredia', nullable: true),
        new OA\Property(property: 'canton', type: 'string', description: 'Cantón', maxLength: 100, example: 'Heredia', nullable: true),
        new OA\Property(property: 'distrito', type: 'string', description: 'Distrito', maxLength: 100, example: 'Mercedes', nullable: true),
        new OA\Property(property: 'limite_credito', type: 'number', format: 'decimal', description: 'Límite de crédito', example: 100000.00),
        new OA\Property(property: 'plazo_credito_dias', type: 'integer', description: 'Plazo de crédito en días', example: 60),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-01 08:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:30:00')
    ]
)]
class ProveedorSchema
{
}
