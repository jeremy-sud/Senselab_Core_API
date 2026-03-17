<?php

namespace App\Exceptions;

use Exception;

/**
 * Excepción base abstracta para errores de dominio.
 *
 * FASE 15: Todas las excepciones de dominio heredan de esta clase.
 * Permite mapeo centralizado a HTTP status codes en el Exception Handler.
 */
abstract class DomainException extends Exception
{
    protected int $httpStatusCode;

    public function __construct(string $message = '', int $httpStatusCode = 422, int $code = 0, ?\Throwable $previous = null)
    {
        $this->httpStatusCode = $httpStatusCode;
        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
}
