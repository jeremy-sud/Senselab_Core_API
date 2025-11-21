<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "TasaImpuesto",
    title: "Tasa de Impuesto",
    description: "Tasa de impuesto con vigencia temporal. Permite mantener histórico de cambios (ej: IVA 13% -> 15%). Tabla global sin empresa_id.",
    type: "object",
    required: ["tipo_impuesto_id", "tasa_porcentaje", "fecha_inicio_vigencia"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            example: 1
        ),
        new OA\Property(
            property: "tipo_impuesto_id",
            type: "integer",
            example: 1,
            description: "ID del tipo de impuesto (relación con TipoImpuesto)"
        ),
        new OA\Property(
            property: "tasa_porcentaje",
            type: "number",
            format: "float",
            example: 13.0,
            description: "Porcentaje de la tasa (0-100)"
        ),
        new OA\Property(
            property: "fecha_inicio_vigencia",
            type: "string",
            format: "date",
            example: "2024-01-01",
            description: "Fecha de inicio de vigencia de la tasa"
        ),
        new OA\Property(
            property: "fecha_fin_vigencia",
            type: "string",
            format: "date",
            nullable: true,
            example: "2024-12-31",
            description: "Fecha de fin de vigencia (null = vigencia indefinida)"
        ),
        new OA\Property(
            property: "descripcion",
            type: "string",
            nullable: true,
            example: "Tasa reducida temporal por emergencia"
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
class TasaImpuestoSchema
{
}
