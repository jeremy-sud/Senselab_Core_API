<?php

namespace App\Http\Controllers\API\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "SalidaInventario",
    title: "Salida de Inventario",
    description: "Esquema para registro de salidas de inventario del sistema",
    required: ["empresa_id", "almacen_id", "fecha_salida", "tipo_salida", "estado"],
    properties: [
        new OA\Property(
            property: "id",
            description: "ID único de la salida de inventario",
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
            description: "ID del almacén desde donde sale el inventario",
            type: "integer",
            example: 2
        ),
        new OA\Property(
            property: "fecha_salida",
            description: "Fecha en que se realiza la salida",
            type: "string",
            format: "date-time",
            example: "2024-01-15T10:30:00Z"
        ),
        new OA\Property(
            property: "tipo_salida",
            description: "Tipo de salida (Venta, Traslado, Ajuste, Devolución, etc.)",
            type: "string",
            enum: ["Venta", "Traslado", "Ajuste", "Devolución", "Producción", "Merma", "Otro"],
            example: "Venta"
        ),
        new OA\Property(
            property: "venta_id",
            description: "ID de la venta relacionada (opcional)",
            type: "integer",
            nullable: true,
            example: 20
        ),
        new OA\Property(
            property: "cliente_id",
            description: "ID del cliente que recibe la mercadería (opcional)",
            type: "integer",
            nullable: true,
            example: 12
        ),
        new OA\Property(
            property: "proveedor_id",
            description: "ID del proveedor en caso de devolución (opcional)",
            type: "integer",
            nullable: true,
            example: 5
        ),
        new OA\Property(
            property: "documento_referencia",
            description: "Número de factura, guía o documento de referencia",
            type: "string",
            nullable: true,
            example: "FACT-2024-050"
        ),
        new OA\Property(
            property: "estado",
            description: "Estado de la salida (Pendiente, Procesada, Cancelada)",
            type: "string",
            enum: ["Pendiente", "Procesada", "Cancelada"],
            example: "Procesada"
        ),
        new OA\Property(
            property: "monto_total",
            description: "Monto total de la salida (decimal con 2 decimales)",
            type: "number",
            format: "decimal",
            example: 85000.00
        ),
        new OA\Property(
            property: "observaciones",
            description: "Observaciones o notas adicionales",
            type: "string",
            nullable: true,
            example: "Entregado completo, firmado por cliente"
        ),
        new OA\Property(
            property: "descripcion",
            description: "Descripción general de la salida",
            type: "string",
            nullable: true,
            example: "Salida de productos para venta #20"
        ),
        new OA\Property(
            property: "activo",
            description: "Indica si la salida está activa",
            type: "boolean",
            example: true
        ),
        new OA\Property(
            property: "eliminado",
            description: "Indica si la salida fue eliminada (soft delete)",
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
class SalidaInventarioSchema
{
}
