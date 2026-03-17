<?php

namespace App\Exceptions;

/**
 * Excepción para operaciones de inventario
 *
 * FASE 15: Extiende DomainException para mapeo automático de HTTP status codes.
 */
class InventarioException extends DomainException
{
    public static function entradaYaProcesada(): self
    {
        return new self('La entrada ya fue procesada anteriormente', 422);
    }

    public static function entradaSinProductos(): self
    {
        return new self('No se puede procesar una entrada sin productos', 422);
    }

    public static function entradaYaProcesadaParaCancelar(): self
    {
        return new self('No se puede cancelar una entrada ya procesada', 422);
    }

    public static function salidaYaProcesada(): self
    {
        return new self('La salida ya fue procesada anteriormente', 422);
    }

    public static function salidaSinProductos(): self
    {
        return new self('No se puede procesar una salida sin productos', 422);
    }

    public static function stockInsuficiente(int $productoId): self
    {
        return new self("Stock insuficiente para el producto ID: {$productoId}", 422);
    }
}
