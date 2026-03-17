<?php

namespace App\Exceptions;

/**
 * Excepción para operaciones de ventas.
 *
 * FASE 15: Excepciones tipadas para módulo de ventas.
 */
class VentaException extends DomainException
{
    public static function estadoInvalido(string $estado): self
    {
        return new self("Estado '{$estado}' no válido para la venta", 422);
    }

    public static function ventaYaFacturada(int $ventaId): self
    {
        return new self("La venta #{$ventaId} ya fue facturada", 409);
    }

    public static function ventaSinDetalle(): self
    {
        return new self('No se puede procesar una venta sin detalles', 422);
    }

    public static function ventaYaAnulada(): self
    {
        return new self('La venta ya fue anulada', 409);
    }
}
