<?php

namespace App\Exceptions;

/**
 * Excepción para violaciones de reglas de negocio genéricas.
 *
 * FASE 16: Para lógica de dominio que no encaja en excepciones específicas
 * (ej: eliminar IVA, catálogos protegidos, restricciones de negocio).
 */
class BusinessException extends DomainException
{
    public function __construct(string $message = 'Operación no permitida por reglas de negocio', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, $code, $previous);
    }
}
