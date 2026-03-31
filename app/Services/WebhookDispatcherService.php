<?php

namespace App\Services;

use App\Jobs\DeliverWebhookJob;
use App\Models\Webhook;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para despachar eventos a webhooks suscritos.
 *
 * FASE 20: Busca webhooks activos para el evento dado y despacha jobs
 * de entrega asíncrona para cada uno.
 */
class WebhookDispatcherService
{
    /**
     * Despacha un evento a todos los webhooks activos suscritos.
     *
     * @param string $evento Nombre del evento (e.g. 'venta.creada')
     * @param int $empresaId ID de la empresa (tenant)
     * @param array<string, mixed> $payload Datos del evento
     */
    public function despachar(string $evento, int $empresaId, array $payload): void
    {
        $webhooks = Webhook::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('activo', true)
            ->where('eliminado', false)
            ->get();

        $despachados = 0;

        foreach ($webhooks as $webhook) {
            if (!$webhook->escuchaEvento($evento)) {
                continue;
            }

            DeliverWebhookJob::dispatch(
                $webhook->id,
                $evento,
                $payload,
                $empresaId,
                1,
                $webhook->max_reintentos,
            );

            $despachados++;
        }

        if ($despachados > 0) {
            Log::info('WebhookDispatcher: Eventos despachados', [
                'evento' => $evento,
                'empresa_id' => $empresaId,
                'webhooks_despachados' => $despachados,
            ]);
        }
    }
}
