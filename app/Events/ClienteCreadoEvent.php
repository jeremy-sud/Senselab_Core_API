<?php

namespace App\Events;

class ClienteCreadoEvent extends WebhookEvent
{
    /**
     * @param int $empresaId
     * @param array<string, mixed> $payload
     */
    public function __construct(int $empresaId, array $payload)
    {
        parent::__construct('cliente.creado', $empresaId, $payload);
    }
}
