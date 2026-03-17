<?php

namespace App\Exceptions;

/**
 * Excepción para operaciones de compras.
 *
 * FASE 15: Excepciones tipadas para módulo de compras.
 */
class CompraException extends DomainException
{
    public static function ordenYaAprobada(int $ordenId): self
    {
        return new self("La orden de compra #{$ordenId} ya fue aprobada", 409);
    }

    public static function ordenYaAnulada(int $ordenId): self
    {
        return new self("La orden de compra #{$ordenId} ya fue anulada", 409);
    }

    public static function proveedorInactivo(int $proveedorId): self
    {
        return new self("El proveedor #{$proveedorId} está inactivo", 422);
    }
}
