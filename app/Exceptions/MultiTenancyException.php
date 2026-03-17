<?php

namespace App\Exceptions;

/**
 * Excepción para operaciones multi-tenancy.
 *
 * FASE 15: Excepciones tipadas para contexto de empresa/tenant.
 */
class MultiTenancyException extends DomainException
{
    public static function empresaNoEncontrada(): self
    {
        return new self('Empresa no encontrada en el contexto actual', 403);
    }

    public static function sinAccesoAEmpresa(int $empresaId): self
    {
        return new self("No tiene acceso a la empresa #{$empresaId}", 403);
    }

    public static function empresaInactiva(int $empresaId): self
    {
        return new self("La empresa #{$empresaId} está inactiva", 403);
    }
}
