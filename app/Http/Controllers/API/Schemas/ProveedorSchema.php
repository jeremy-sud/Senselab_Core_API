<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Proveedor',
    title: 'Proveedor',
    description: 'Proveedor del sistema',
    required: ['id', 'empresa_id', 'tipo_identificacion', 'numero_identificacion', 'nombre'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'empresa_id', type: 'integer', example: 1),
        new OA\Property(property: 'tipo_identificacion', type: 'string', enum: ['01', '02', '03', '04', '05'], example: '02', description: '01=Cédula física, 02=Cédula jurídica, 03=DIMEX, 04=NITE, 05=Extranjero'),
        new OA\Property(property: 'numero_identificacion', type: 'string', example: '3-101-123456'),
        new OA\Property(property: 'nombre', type: 'string', example: 'Distribuidora Nacional S.A.'),
        new OA\Property(property: 'nombre_comercial', type: 'string', nullable: true, example: 'DINASA'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'ventas@dinasa.com'),
        new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '2222-3333'),
        new OA\Property(property: 'direccion', type: 'string', nullable: true, example: 'San José, Curridabat, frente al ITCR'),
        new OA\Property(property: 'provincia', type: 'string', nullable: true, example: 'San José'),
        new OA\Property(property: 'canton', type: 'string', nullable: true, example: 'Curridabat'),
        new OA\Property(property: 'distrito', type: 'string', nullable: true, example: 'Curridabat'),
        new OA\Property(property: 'limite_credito', type: 'number', format: 'decimal', nullable: true, example: 1000000.00),
        new OA\Property(property: 'plazo_credito_dias', type: 'integer', nullable: true, example: 60),
        new OA\Property(property: 'activo', type: 'boolean', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'empresa', ref: '#/components/schemas/Empresa')
    ]
)]
class ProveedorSchema
{
}
