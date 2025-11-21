<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AsientoContable",
    title: "Asiento Contable",
    description: "Esquema para registro de asientos contables con sistema de doble partida",
    required: ["empresa_id", "fecha_asiento", "estado"],
    properties: [
        new OA\Property(
            property: "id",
            description: "ID único del asiento contable",
            type: "integer",
            example: 1
        ),
        new OA\Property(
            property: "empresa_id",
            description: "ID de la empresa (multi-tenant)",
            type: "integer",
            example: 1
        ),
        new OA\Property(
            property: "fecha_asiento",
            description: "Fecha en que se registra el asiento contable",
            type: "string",
            format: "date-time",
            example: "2024-01-15T10:30:00Z"
        ),
        new OA\Property(
            property: "descripcion",
            description: "Descripción del asiento contable",
            type: "string",
            nullable: true,
            example: "Registro de venta #1234"
        ),
        new OA\Property(
            property: "total_debe",
            description: "Total del debe (suma de todos los débitos). Debe ser igual al total_haber",
            type: "number",
            format: "decimal",
            example: 150000.00
        ),
        new OA\Property(
            property: "total_haber",
            description: "Total del haber (suma de todos los créditos). Debe ser igual al total_debe",
            type: "number",
            format: "decimal",
            example: 150000.00
        ),
        new OA\Property(
            property: "estado",
            description: "Estado del asiento (Borrador, Mayorizado, Anulado)",
            type: "string",
            enum: ["Borrador", "Mayorizado", "Anulado"],
            example: "Mayorizado"
        ),
        new OA\Property(
            property: "activo",
            description: "Indica si el asiento está activo",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "eliminado",
            description: "Indica si el asiento fue eliminado (soft delete)",
            type: "boolean",
            example: false
        ),
        new OA\Property(
            property: "creado_en",
            description: "Fecha de creación del registro",
            type: "string",
            format: "date-time",
            example: "2024-01-15T10:30:00Z"
        ),
        new OA\Property(
            property: "actualizado_en",
            description: "Fecha de última actualización",
            type: "string",
            format: "date-time",
            example: "2024-01-15T14:45:00Z"
        )
    ]
)]
class AsientoContableSchema
{
}
