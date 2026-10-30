<?php

namespace App\Exceptions;

class TripChangeNotAllowedException extends DomainException
{
    public function __construct(string $message = 'No es posible realizar el cambio de viaje solicitado.', int $httpStatusCode = 422, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $httpStatusCode, $code, $previous);
    }
}
