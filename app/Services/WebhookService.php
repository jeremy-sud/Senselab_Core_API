<?php

namespace App\Services;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Servicio para gestionar Webhooks (CRUD).
 *
 * @extends BaseService<Webhook>
 */
class WebhookService extends BaseService
{
    protected string $modelClass = Webhook::class;

    /** @var string[] */
    protected array $searchFields = ['nombre', 'url', 'descripcion'];

    /** @var array<int, string> */
    protected array $defaultRelations = ['empresa'];

    /**
     * @param Builder<Webhook> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        $query->where('eliminado', false);

        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (isset($filtros['activo'])) {
            $query->where('activo', (bool) $filtros['activo']);
        }

        if (!empty($filtros['evento'])) {
            $query->whereJsonContains('eventos', $filtros['evento']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function beforeCreate(array &$data): void
    {
        if (empty($data['secret'])) {
            $data['secret'] = Str::random(64);
        }
    }

    /**
     * Obtiene los logs de un webhook específico.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator<int, WebhookLog>
     */
    public function obtenerLogs(int $webhookId, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator
    {
        return WebhookLog::where('webhook_id', $webhookId)
            ->orderByDesc('creado_en')
            ->paginate($perPage);
    }

    /**
     * Regenera el secret de un webhook y lo retorna.
     */
    public function regenerarSecret(Webhook $webhook): string
    {
        $nuevoSecret = Str::random(64);
        $webhook->update(['secret' => $nuevoSecret]);
        return $nuevoSecret;
    }

    /**
     * Prueba la conectividad de un webhook enviando un evento de test.
     *
     * @return array{exitoso: bool, codigo_respuesta: int|null, latencia_ms: int|null, error: string|null}
     */
    public function probar(Webhook $webhook): array
    {
        $payload = json_encode([
            'evento' => 'webhook.test',
            'timestamp' => now()->toIso8601String(),
            'datos' => ['mensaje' => 'Prueba de conectividad'],
        ], JSON_THROW_ON_ERROR);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $webhook->secret);
        $startTime = microtime(true);

        try {
            $response = \Illuminate\Support\Facades\Http::timeout($webhook->timeout_segundos)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => 'webhook.test',
                    'X-Webhook-Id' => (string) $webhook->id,
                    'User-Agent' => 'Ursol-CAST-API/4.2.0',
                ])
                ->withBody($payload, 'application/json')
                ->post($webhook->url);

            $latencia = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'exitoso' => $response->successful(),
                'codigo_respuesta' => $response->status(),
                'latencia_ms' => $latencia,
                'error' => $response->successful() ? null : 'HTTP ' . $response->status(),
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $latencia = (int) ((microtime(true) - $startTime) * 1000);
            return [
                'exitoso' => false,
                'codigo_respuesta' => null,
                'latencia_ms' => $latencia,
                'error' => 'Error de conexión',
            ];
        }
    }
}
