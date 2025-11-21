<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Empresa',
    title: 'Empresa',
    description: 'Empresa del sistema (tenant)',
    required: ['id', 'nombre', 'identificacion'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', example: 'Sistemas Ursol S.A.'),
        new OA\Property(property: 'nombre_comercial', type: 'string', nullable: true, example: 'Ursol'),
        new OA\Property(property: 'identificacion', type: 'string', example: '3-101-123456'),
        new OA\Property(property: 'tipo_identificacion', type: 'string', enum: ['CEDULA_FISICA', 'CEDULA_JURIDICA', 'DIMEX', 'NITE'], example: 'CEDULA_JURIDICA'),
        new OA\Property(property: 'telefono', type: 'string', nullable: true, example: '2222-3333'),
        new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true, example: 'info@ursol.com'),
        new OA\Property(property: 'activo', type: 'boolean', example: true)
    ]
)]
class EmpresaSchema
{
}
