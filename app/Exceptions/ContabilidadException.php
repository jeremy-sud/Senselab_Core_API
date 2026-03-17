<?php

namespace App\Exceptions;

/**
 * Excepción para operaciones contables.
 *
 * FASE 15: Excepciones tipadas para módulo de contabilidad.
 */
class ContabilidadException extends DomainException
{
    public static function asientoDesbalanceado(float $debitos, float $creditos): self
    {
        return new self(
            "El asiento contable está desbalanceado: débitos ({$debitos}) != créditos ({$creditos})",
            422
        );
    }

    public static function periodoContableCerrado(string $periodo): self
    {
        return new self("El período contable '{$periodo}' está cerrado", 422);
    }

    public static function cuentaContableInactiva(string $codigo): self
    {
        return new self("La cuenta contable '{$codigo}' está inactiva", 422);
    }

    public static function presupuestoExcedido(string $detalle): self
    {
        return new self("Presupuesto excedido: {$detalle}", 422);
    }
}
