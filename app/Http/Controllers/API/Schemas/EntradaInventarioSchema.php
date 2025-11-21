<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "EntradaInventario",
    title: "Entrada de Inventario",
    description: "Esquema para registro de entradas de inventario al sistema",
    required: ["empresa_id", "almacen_id", "fecha_entrada", "tipo_entrada", "estado"],
    properties: [
        new OA\Property(
            property: "id",
            description: "ID único de la entrada de inventario",
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
            property: "almacen_id",
            description: "ID del almacén donde ingresa el inventario",
            type: "integer",
            example: 2
        ),
        new OA\Property(
            property: "fecha_entrada",
            description: "Fecha en que se realiza la entrada",
            type: "string",
            format: "date-time",
            example: "2024-01-15T10:30:00Z"
        ),
        new OA\Property(
            property: "tipo_entrada",
            description: "Tipo de entrada (Compra, Traslado, Ajuste, Devolución, etc.)",
            type: "string",
            enum: ["Compra", "Traslado", "Ajuste", "Devolución", "Producción", "Otro"],
            example: "Compra"
        ),
        new OA\Property(
            property: "orden_compra_id",
            description: "ID de la orden de compra relacionada (opcional)",
            type: "integer",
            nullable: true,
            example: 15
        ),
        new OA\Property(
            property: "proveedor_id",
            description: "ID del proveedor que envía la mercadería (opcional)",
            type: "integer",
            nullable: true,
            example: 5
        ),
        new OA\Property(
            property: "documento_referencia",
            description: "Número de factura, guía o documento de referencia",
            type: "string",
            nullable: true,
            example: "FACT-2024-001"
        ),
        new OA\Property(
            property: "estado",
            description: "Estado de la entrada (Pendiente, Procesada, Cancelada)",
            type: "string",
            enum: ["Pendiente", "Procesada", "Cancelada"],
            example: "Pendiente"
        ),
        new OA\Property(
            property: "monto_total",
            description: "Monto total de la entrada (decimal con 2 decimales)",
            type: "number",
            format: "decimal",
            example: 125000.00
        ),
        new OA\Property(
            property: "observaciones",
            description: "Observaciones o notas adicionales",
            type: "string",
            nullable: true,
            example: "Recibido en buen estado, verificado cantidad"
        ),
        new OA\Property(
            property: "descripcion",
            description: "Descripción general de la entrada",
            type: "string",
            nullable: true,
            example: "Entrada de mercadería según orden de compra #15"
        ),
        new OA\Property(
            property: "activo",
            description: "Indica si la entrada está activa",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "eliminado",
            description: "Indica si la entrada fue eliminada (soft delete)",
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
class EntradaInventarioSchema
{
}
