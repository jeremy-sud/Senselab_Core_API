<?php

namespace App\Exceptions;

/**
 * Excepción para operaciones de nómina.
 *
 * FASE 15: Excepciones tipadas para módulo de nómina/RRHH.
 */
class NominaException extends DomainException
{
    public static function periodoYaCerrado(string $periodo): self
    {
        return new self("El período de nómina '{$periodo}' ya está cerrado", 422);
    }

    public static function empleadoInactivo(int $empleadoId): self
    {
        return new self("El empleado #{$empleadoId} está inactivo", 422);
    }

    public static function pagoYaProcesado(int $pagoId): self
    {
        return new self("El pago de nómina #{$pagoId} ya fue procesado", 409);
    }

    public static function planillaYaGenerada(string $periodo): self
    {
        return new self("La planilla CCSS del período '{$periodo}' ya fue generada", 409);
    }
}
