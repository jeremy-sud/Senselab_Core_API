<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Caby",
    title: "CAByS - Catálogo de Bienes y Servicios",
    description: "Código CAByS del catálogo oficial de Costa Rica para clasificación fiscal de productos y servicios. Tabla global sin empresa_id.",
    type: "object",
    required: ["codigo", "descripcion"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            example: 1
        ),
        new OA\Property(
            property: "codigo",
            type: "string",
            maxLength: 20,
            example: "8529901000000",
            description: "Código CAByS según catálogo de Hacienda (hasta 20 caracteres)"
        ),
        new OA\Property(
            property: "descripcion",
            type: "string",
            example: "Antenas de telefonía móvil",
            description: "Descripción oficial del bien o servicio"
        ),
        new OA\Property(
            property: "impuesto_iva_predeterminado",
            type: "number",
            format: "decimal",
            example: 13.00,
            description: "Porcentaje de IVA predeterminado para este código"
        ),
        new OA\Property(
            property: "activo",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "eliminado",
            type: "boolean",
            example: false
        ),
        new OA\Property(
            property: "creado_en",
            type: "string",
            format: "date-time",
            example: "2024-01-15T10:30:00Z"
        ),
        new OA\Property(
            property: "actualizado_en",
            type: "string",
            format: "date-time",
            example: "2024-01-15T10:30:00Z"
        )
    ]
)]
class CabySchema
{
}
