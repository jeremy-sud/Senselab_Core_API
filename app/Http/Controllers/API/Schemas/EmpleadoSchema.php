<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Empleado',
    title: 'Empleado',
    description: 'Empleado de la empresa',
    required: ['id', 'empresa_id', 'nombre', 'apellidos', 'numero_identificacion'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'cargo_id', type: 'integer', nullable: true, example: 3),
        new OA\Property(property: 'numero_identificacion', type: 'string', example: '1-2345-6789'),
        new OA\Property(property: 'nombre', type: 'string', example: 'Carlos'),
        new OA\Property(property: 'apellidos', type: 'string', example: 'Rodríguez Mora'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'carlos.rodriguez@empresa.com'),
        new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '8888-9999'),
        new OA\Property(property: 'direccion', type: 'string', nullable: true, example: 'Heredia, San Francisco'),
        new OA\Property(property: 'fecha_ingreso', type: 'string', format: 'date', nullable: true, example: '2023-01-15'),
        new OA\Property(property: 'salario_base', type: 'number', format: 'decimal', nullable: true, example: 650000.00),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'cargo', ref: '#/components/schemas/Cargo', nullable: true)
    ]
)]
class EmpleadoSchema
{
}
