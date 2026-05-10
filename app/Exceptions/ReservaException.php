<?php

namespace App\Exceptions;

class ReservaException extends DomainException
{
    public static function invalidState(string $state): self
    {
        return new self("Estado de reserva inválido: {$state}");
    }
}
