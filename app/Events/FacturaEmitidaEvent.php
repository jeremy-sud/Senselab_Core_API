<?php

namespace App\Events;

class FacturaEmitidaEvent extends WebhookEvent
{
    /**
     * @param int $empresaId
     * @param array<string, mixed> $payload
     */
    public function __construct(int $empresaId, array $payload)
    {
        parent::__construct('factura.emitida', $empresaId, $payload);
    }
}
