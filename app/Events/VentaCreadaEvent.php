<?php

namespace App\Events;

class VentaCreadaEvent extends WebhookEvent
{
    /**
     * @param int $empresaId
     * @param array<string, mixed> $payload
     */
    public function __construct(int $empresaId, array $payload)
    {
        parent::__construct('venta.creada', $empresaId, $payload);
    }
}
