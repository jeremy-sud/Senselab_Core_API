<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Pago",
    title: "Pago",
    description: "Esquema para registro de pagos en el sistema",
    required: ["empresa_id", "fecha_pago", "monto", "estado"],
    properties: [
        new OA\Property(
            property: "id",
            description: "ID único del pago",
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
            property: "orden_compra_id",
            description: "ID de la orden de compra relacionada (opcional)",
            type: "integer",
            nullable: true,
            example: 15
        ),
        new OA\Property(
            property: "cuenta_por_pagar_id",
            description: "ID de la cuenta por pagar (opcional)",
            type: "integer",
            nullable: true,
            example: 8
        ),
        new OA\Property(
            property: "proveedor_id",
            description: "ID del proveedor que recibe el pago (opcional)",
            type: "integer",
            nullable: true,
            example: 5
        ),
        new OA\Property(
            property: "cliente_id",
            description: "ID del cliente que realiza el pago (opcional)",
            type: "integer",
            nullable: true,
            example: 12
        ),
        new OA\Property(
            property: "cuenta_por_cobrar_id",
            description: "ID de la cuenta por cobrar (opcional)",
            type: "integer",
            nullable: true,
            example: 20
        ),
        new OA\Property(
            property: "forma_pago_id",
            description: "ID de la forma de pago utilizada",
            type: "integer",
            nullable: true,
            example: 2
        ),
        new OA\Property(
            property: "fecha_pago",
            description: "Fecha en que se realizó el pago",
            type: "string",
            format: "date",
            example: "2024-01-15"
        ),
        new OA\Property(
            property: "monto",
            description: "Monto del pago (decimal con 2 decimales)",
            type: "number",
            format: "decimal",
            example: 15000.50
        ),
        new OA\Property(
            property: "moneda",
            description: "Código de moneda (CRC, USD, etc.)",
            type: "string",
            maxLength: 3,
            nullable: true,
            example: "CRC"
        ),
        new OA\Property(
            property: "descripcion",
            description: "Descripción detallada del pago",
            type: "string",
            nullable: true,
            example: "Pago parcial de factura #1234"
        ),
        new OA\Property(
            property: "referencia",
            description: "Número de referencia, comprobante o código de transacción",
            type: "string",
            nullable: true,
            example: "REF-2024-001"
        ),
        new OA\Property(
            property: "estado",
            description: "Estado del pago (Pendiente, Pagado, Cancelado)",
            type: "string",
            enum: ["Pendiente", "Pagado", "Cancelado"],
            example: "Pagado"
        ),
        new OA\Property(
            property: "activo",
            description: "Indica si el pago está activo",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "eliminado",
            description: "Indica si el pago fue eliminado (soft delete)",
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
class PagoSchema
{
}
