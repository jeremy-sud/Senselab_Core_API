<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Cliente',
    title: 'Cliente',
    description: 'Cliente del sistema',
    required: ['id', 'empresa_id', 'tipo_identificacion', 'numero_identificacion', 'nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'tipo_identificacion', type: 'string', enum: ['01', '02', '03', '04', '05', '06', '07'], example: '01', description: '01=Cédula física, 02=Cédula jurídica, 03=DIMEX, 04=NITE, 05=Extranjero, 06=Identificación sin país, 07=Pasaporte'),
        new OA\Property(property: 'numero_identificacion', type: 'string', example: '1-2345-6789'),
        new OA\Property(property: 'nombre', type: 'string', example: 'Juan'),
        new OA\Property(property: 'apellidos', type: 'string', nullable: true, example: 'Pérez González'),
        new OA\Property(property: 'nombre_comercial', type: 'string', nullable: true, example: 'Comercial JP'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'juan.perez@example.com'),
        new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '8888-7777'),
        new OA\Property(property: 'direccion', type: 'string', nullable: true, example: 'San José, Escazú, del mall 200m oeste'),
        new OA\Property(property: 'provincia', type: 'string', nullable: true, example: 'San José'),
        new OA\Property(property: 'canton', type: 'string', nullable: true, example: 'Escazú'),
        new OA\Property(property: 'distrito', type: 'string', nullable: true, example: 'San Rafael'),
        new OA\Property(property: 'limite_credito', type: 'number', format: 'decimal', nullable: true, example: 500000.00),
        new OA\Property(property: 'plazo_credito_dias', type: 'integer', nullable: true, example: 30),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'empresa', ref: '#/components/schemas/Empresa')
    ]
)]
class ClienteSchema
{
}
