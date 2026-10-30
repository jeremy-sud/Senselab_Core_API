<?php

namespace App\Exceptions;

class SeatHoldConflictException extends DomainException
{
    public function __construct(string $message = 'El asiento solicitado no se encuentra disponible.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, 409, $code, $previous);
    }
}
