<?php

namespace App\Http\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Usuario',
    required: ['nombre', 'email', 'password_hash', 'empresa_id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'nombre', type: 'string', description: 'Nombre del usuario', maxLength: 255, example: 'Juan'),
        new OA\Property(property: 'apellidos', type: 'string', description: 'Apellidos del usuario', maxLength: 255, example: 'Pérez González', nullable: true),
        new OA\Property(property: 'cargo_id', type: 'integer', description: 'ID del cargo', example: 2, nullable: true),
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Email del usuario', maxLength: 255, example: 'juan.perez@example.com'),
        new OA\Property(property: 'password_hash', type: 'string', description: 'Hash de la contraseña', maxLength: 255),
        new OA\Property(property: 'empresa_id', type: 'integer', description: 'ID de la empresa', example: 1),
        new OA\Property(property: 'telefono', type: 'string', description: 'Teléfono de contacto', maxLength: 50, example: '+506 8888-9999', nullable: true),
        new OA\Property(property: 'direccion', type: 'string', description: 'Dirección', nullable: true),
        new OA\Property(property: 'activo', type: 'boolean', description: 'Indica si está activo', example: true),
        new OA\Property(property: 'eliminado', type: 'boolean', description: 'Indica si está eliminado', example: false),
        new OA\Property(property: 'creado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00'),
        new OA\Property(property: 'actualizado_en', type: 'string', format: 'date-time', example: '2025-01-15 10:00:00')
    ]
)]
class UsuarioSchema
{
}
