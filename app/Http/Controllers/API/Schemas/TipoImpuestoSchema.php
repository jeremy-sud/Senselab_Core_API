<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "TipoImpuesto",
    title: "Tipo de Impuesto",
    description: "Tipo de impuesto para facturación electrónica. Incluye códigos de Hacienda Costa Rica. Tabla global sin empresa_id.",
    type: "object",
    required: ["codigo_hacienda", "nombre"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            example: 1
        ),
        new OA\Property(
            property: "codigo_hacienda",
            type: "string",
            maxLength: 2,
            example: "01",
            description: "Código de Hacienda Costa Rica (01=IVA, 02=Consumo, etc.)"
        ),
        new OA\Property(
            property: "nombre",
            type: "string",
            maxLength: 100,
            example: "Impuesto al Valor Agregado"
        ),
        new OA\Property(
            property: "descripcion",
            type: "string",
            nullable: true,
            example: "IVA aplicable a la mayoría de bienes y servicios"
        ),
        new OA\Property(
            property: "comentario",
            type: "string",
            nullable: true,
            example: "Tasa estándar 13%"
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
class TipoImpuestoSchema
{
}
