<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "TipoCuenta",
    title: "Tipo de Cuenta Contable",
    description: "Tipo de cuenta contable (Activo, Pasivo, Patrimonio, Ingresos, Costos, Gastos). Tabla global sin empresa_id.",
    type: "object",
    required: ["nombre", "naturaleza"],
    properties: [
        new OA\Property(
            property: "id",
            type: "integer",
            example: 1
        ),
        new OA\Property(
            property: "nombre",
            type: "string",
            maxLength: 100,
            example: "Activo"
        ),
        new OA\Property(
            property: "descripcion",
            type: "string",
            nullable: true,
            example: "Representa los bienes y derechos de la empresa"
        ),
        new OA\Property(
            property: "naturaleza",
            type: "string",
            enum: ["Deudora", "Acreedora"],
            example: "Deudora",
            description: "Naturaleza contable del tipo de cuenta"
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
class TipoCuentaSchema
{
}
