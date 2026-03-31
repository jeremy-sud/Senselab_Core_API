<?php

namespace App\Events;

class PagoRecibidoEvent extends WebhookEvent
{
    /**
     * @param int $empresaId
     * @param array<string, mixed> $payload
     */
    public function __construct(int $empresaId, array $payload)
    {
        parent::__construct('pago.recibido', $empresaId, $payload);
    }
}
