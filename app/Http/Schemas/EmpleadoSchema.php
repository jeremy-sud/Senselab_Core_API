<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Empleado',
    required: ['empresa_id', 'nombre', 'primer_apellido', 'tipo_documento', 'numero_documento'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre del empleado', maxLength: 255, example: 'Carlos'),
        new OA\Property(property: 'primer_apellido', type: 'string', description: 'Primer apellido', maxLength: 255, example: 'Rodríguez'),
        new OA\Property(property: 'segundo_apellido', type: 'string', description: 'Segundo apellido', maxLength: 255, example: 'Mora', nullable: true),
        new OA\Property(property: 'tipo_documento', type: 'string', description: 'Tipo de documento', maxLength: 50, example: 'Cédula'),
        new OA\Property(property: 'numero_documento', type: 'string', description: 'Número de documento', maxLength: 100, example: '1-0234-0567'),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Email del empleado', maxLength: 255, example: 'carlos@example.com', nullable: true),
        new OA\Property(property: 'telefono', type: 'string', description: 'Teléfono', maxLength: 50, example: '+506 7777-8888', nullable: true),
        new OA\Property(property: 'direccion', type: 'string', description: 'Dirección', nullable: true),
        new OA\Property(property: 'fecha_nacimiento', type: 'string', format: 'date', description: 'Fecha de nacimiento', example: '1990-05-15', nullable: true),
        new OA\Property(property: 'fecha_ingreso', type: 'string', format: 'date', description: 'Fecha de ingreso', example: '2020-01-10', nullable: true),
        new OA\Property(property: 'cargo_id', type: 'integer', description: 'ID del cargo', example: 3, nullable: true),
        new OA\Property(property: 'salario', type: 'number', format: 'decimal', description: 'Salario', example: 650000.00),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(
            property: 'cargo',
            ref: '#/components/schemas/Cargo',
            description: 'Cargo del empleado',
            nullable: true
        )
    ]
)]
class EmpleadoSchema
{
}
