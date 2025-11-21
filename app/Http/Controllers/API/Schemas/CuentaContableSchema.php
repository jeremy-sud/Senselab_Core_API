<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CuentaContable",
    title: "Cuenta Contable",
    description: "Esquema para Plan Único de Cuentas (PUC) contables con estructura jerárquica",
    required: ["empresa_id", "nombre", "codigo", "tipo_cuenta_id"],
    properties: [
        new OA\Property(
            property: "id",
            description: "ID único de la cuenta contable",
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
            property: "nombre",
            description: "Nombre de la cuenta contable",
            type: "string",
            example: "Bancos"
        ),
        new OA\Property(
            property: "descripcion",
            description: "Descripción detallada de la cuenta",
            type: "string",
            nullable: true,
            example: "Cuentas bancarias de la empresa"
        ),
        new OA\Property(
            property: "codigo",
            description: "Código único de la cuenta (debe ser único por empresa)",
            type: "string",
            example: "1105"
        ),
        new OA\Property(
            property: "tipo_cuenta_id",
            description: "ID del tipo de cuenta (Activo, Pasivo, Patrimonio, Ingresos, Gastos)",
            type: "integer",
            example: 1
        ),
        new OA\Property(
            property: "cuenta_padre_id",
            description: "ID de la cuenta padre (para estructura jerárquica)",
            type: "integer",
            nullable: true,
            example: 5
        ),
        new OA\Property(
            property: "permite_movimientos",
            description: "Indica si la cuenta permite registrar movimientos contables",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "saldo_actual",
            description: "Saldo actual de la cuenta (decimal con 2 decimales)",
            type: "number",
            format: "decimal",
            example: 250000.00
        ),
        new OA\Property(
            property: "activo",
            description: "Indica si la cuenta está activa",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "eliminado",
            description: "Indica si la cuenta fue eliminada (soft delete)",
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
class CuentaContableSchema
{
}
