<?php

namespace App\Http\Controllers\API;

/**
 * Constantes comunes para controllers API
 *
 * @package App\Http\Controllers\API
 */
final class ApiConstants
{
    // Mensajes de respuesta HTTP comunes
    public const MSG_SERVER_ERROR = 'Error del servidor';
    public const MSG_NOT_AUTHENTICATED = 'No autenticado';
    public const MSG_NOT_FOUND = 'No encontrado';
    public const MSG_VALIDATION_ERROR = 'Error de validación';
    public const MSG_FORBIDDEN = 'Acceso denegado';

    // Mensajes de empresas
    public const MSG_EMPRESA_NOT_FOUND = 'Empresa no encontrada';
    public const MSG_EMPRESA_CREATED = 'Empresa creada exitosamente';
    public const MSG_EMPRESA_UPDATED = 'Empresa actualizada exitosamente';
    public const MSG_EMPRESA_DELETED = 'Empresa eliminada exitosamente';
    public const DESC_EMPRESA_ID = 'ID de la empresa';

    // Mensajes de ventas
    public const MSG_VENTA_NOT_FOUND = 'Venta no encontrada';
    public const MSG_VENTA_UPDATED = 'Venta actualizada exitosamente';
    public const MSG_VENTA_CANCELLED = 'Venta anulada exitosamente';
    public const DESC_VENTA_ID = 'ID de la venta';

    // Mensajes de inventario
    public const MSG_ENTRADA_NOT_FOUND = 'Entrada no encontrada';

    // Mensajes de movimientos bancarios
    public const MSG_MOVIMIENTO_NOT_FOUND = 'Movimiento bancario no encontrado';

    // Schemas OpenAPI
    public const SCHEMA_EMPRESA = '#/components/schemas/Empresa';
    public const SCHEMA_VENTA = '#/components/schemas/Venta';
    public const SCHEMA_ENTRADA_INVENTARIO = '#/components/schemas/EntradaInventario';
}
