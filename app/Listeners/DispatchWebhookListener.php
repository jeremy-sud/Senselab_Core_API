<?php

namespace App\Listeners;

use App\Events\WebhookEvent;
use App\Services\WebhookDispatcherService;

/**
 * Listener que escucha todos los WebhookEvent y los despacha
 * a los webhooks configurados del tenant correspondiente.
 */
class DispatchWebhookListener
{
    public function __construct(
        private readonly WebhookDispatcherService $dispatcher,
    ) {}

    public function handle(WebhookEvent $event): void
    {
        $this->dispatcher->despachar(
            $event->evento,
            $event->empresaId,
            $event->payload,
        );
    }
}
