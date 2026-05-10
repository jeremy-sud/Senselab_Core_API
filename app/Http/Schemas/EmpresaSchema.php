<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Empresa',
    required: ['nombre', 'num_identificacion_dgt'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre de la empresa', maxLength: 255, example: 'Senselab'),
        new OA\Property(property: 'nombre_comercial', type: 'string', description: 'Nombre comercial', maxLength: 255, example: 'Senselab', nullable: true),
        new OA\Property(property: 'razon_social', type: 'string', description: 'Razón social', maxLength: 255, nullable: true),
        new OA\Property(property: 'num_identificacion_dgt', type: 'string', description: 'Cédula jurídica', maxLength: 20, example: '3-101-123456'),
        new OA\Property(property: 'tipo_identificacion', type: 'string', description: 'Tipo de identificación DGT', maxLength: 2, example: '02', nullable: true),
        new OA\Property(property: 'actividad_economica_principal', type: 'string', description: 'Código actividad económica', maxLength: 6, nullable: true),
        new OA\Property(property: 'direccion', type: 'string', description: 'Dirección física', nullable: true),
        new OA\Property(property: 'provincia', type: 'string', description: 'Código de provincia', maxLength: 2, example: '1', nullable: true),
        new OA\Property(property: 'canton', type: 'string', description: 'Código de cantón', maxLength: 2, example: '01', nullable: true),
        new OA\Property(property: 'distrito', type: 'string', description: 'Código de distrito', maxLength: 2, example: '01', nullable: true),
        new OA\Property(property: 'telefono', type: 'string', description: 'Teléfono principal', maxLength: 50, example: '+(506)0000-0000', nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Email de contacto', maxLength: 255, example: 'deadmooncr@gmail.com', nullable: true),
        new OA\Property(property: 'prefijo_orden_compra', type: 'string', description: 'Prefijo para órdenes de compra', maxLength: 20, example: 'OC-', nullable: true),
        new OA\Property(property: 'moneda_defecto', type: 'string', description: 'Moneda por defecto', maxLength: 3, example: 'CRC'),
        new OA\Property(property: 'regimen_tributario_id', type: 'integer', description: 'ID del régimen tributario', example: 1, nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activa', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminada', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00')
    ]
)]
class EmpresaSchema
{
}
