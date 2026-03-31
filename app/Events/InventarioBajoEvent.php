<?php

namespace App\Events;

class InventarioBajoEvent extends WebhookEvent
{
    /**
     * @param int $empresaId
     * @param array<string, mixed> $payload
     */
    public function __construct(int $empresaId, array $payload)
    {
        parent::__construct('inventario.bajo', $empresaId, $payload);
    }
}
