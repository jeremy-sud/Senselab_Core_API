<?php

namespace App\Exceptions;

class FailedBookingAuditException extends DomainException
{
    public function __construct(string $message = 'Error durante la auditoría de la reserva.', int $httpStatusCode = 422, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $httpStatusCode, $code, $previous);
    }
}
