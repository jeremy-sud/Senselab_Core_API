<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento base para webhooks.
 *
 * Todos los eventos que deben disparar webhooks extienden esta clase.
 * Contiene el nombre del evento, los datos del payload y el empresa_id.
 */
abstract class WebhookEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param string $evento Nombre del evento (e.g. 'venta.creada')
     * @param int $empresaId ID de la empresa (tenant)
     * @param array<string, mixed> $payload Datos del evento
     */
    public function __construct(
        public readonly string $evento,
        public readonly int $empresaId,
        public readonly array $payload,
    ) {}
}
