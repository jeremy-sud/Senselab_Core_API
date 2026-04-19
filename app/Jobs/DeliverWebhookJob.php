<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Job para entregar un webhook a la URL configurada.
 *
 * FASE 20: Entrega asíncrona con retry exponencial y firma HMAC-SHA256.
 */
class DeliverWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 60;

    /**
     * @param int $webhookId
     * @param string $evento
     * @param array<string, mixed> $payload
     * @param int $empresaId
     * @param int $intento
     * @param int $maxReintentos
     */
    public function __construct(
        public readonly int $webhookId,
        public readonly string $evento,
        public readonly array $payload,
        public readonly int $empresaId,
        public readonly int $intento = 1,
        public readonly int $maxReintentos = 3,
    ) {
        $this->onQueue('webhooks');
    }

    /**
     * Rangos de IPs privadas/reservadas no permitidas como destino de webhooks (SSRF protection).
     *
     * @var array<string>
     */
    private const BLOCKED_IP_RANGES = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '0.0.0.0/8',
        '::1/128',
        'fc00::/7',
        'fe80::/10',
    ];

    public function handle(): void
    {
        /** @var Webhook|null $webhook */
        $webhook = Webhook::withoutGlobalScopes()->find($this->webhookId);

        if (!$webhook || !$webhook->activo || $webhook->eliminado) {
            Log::info('DeliverWebhookJob: Webhook no activo o eliminado', [
                'webhook_id' => $this->webhookId,
                'evento' => $this->evento,
            ]);
            return;
        }

        // SSRF Protection: validar que la URL destino no apunte a IPs privadas/internas
        if (!$this->isUrlSafe($webhook->url)) {
            Log::warning('DeliverWebhookJob: URL destino bloqueada por validación SSRF', [
                'webhook_id' => $this->webhookId,
                'url' => $webhook->url,
            ]);
            return;
        }

        // @phpstan-ignore-next-line
        $payloadJson = json_encode($this->buildPayload(), JSON_THROW_ON_ERROR);
        $signature = $this->generateSignature($payloadJson, $webhook->secret);
        $startTime = microtime(true);

        $log = WebhookLog::create([
            'webhook_id' => $this->webhookId,
            'empresa_id' => $this->empresaId,
            'evento' => $this->evento,
            'estado' => WebhookLog::ESTADO_PENDIENTE,
            'payload' => $this->payload,
            'payload_size' => strlen($payloadJson),
            'intento' => $this->intento,
        ]);

        try {
            $response = Http::timeout($webhook->timeout_segundos)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $this->evento,
                    'X-Webhook-Id' => (string) $this->webhookId,
                    'X-Webhook-Timestamp' => (string) time(),
                    'User-Agent' => 'Ursol-CAST-API/4.2.0',
                ])
                ->withBody($payloadJson, 'application/json')
                ->post($webhook->url);

            $latencia = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $log->update([
                    'estado' => WebhookLog::ESTADO_EXITOSO,
                    'codigo_respuesta' => $response->status(),
                    'latencia_ms' => $latencia,
                    'respuesta' => mb_substr($response->body(), 0, 2000),
                ]);

                Log::info('DeliverWebhookJob: Entrega exitosa', [
                    'webhook_id' => $this->webhookId,
                    'evento' => $this->evento,
                    'status' => $response->status(),
                    'latencia_ms' => $latencia,
                ]);
            } else {
                $this->handleFailure($log, $webhook, $latencia, $response->status(), $response->body());
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $latencia = (int) ((microtime(true) - $startTime) * 1000);
            $this->handleFailure($log, $webhook, $latencia, null, $e->getMessage());
        }
    }

    /**
     * @internal Usado en pruebas unitarias
     */
    private function handleFailure(
        WebhookLog $log,
        Webhook $webhook,
        int $latencia,
        ?int $statusCode,
        ?string $error
    ): void {
        $puedeReintentar = $this->intento < $this->maxReintentos;
        $proximoReintento = $puedeReintentar
            ? now()->addSeconds($this->calcularBackoff())
            : null;

        $log->update([
            'estado' => WebhookLog::ESTADO_FALLIDO,
            'codigo_respuesta' => $statusCode,
            'latencia_ms' => $latencia,
            'error' => mb_substr($error ?? 'Error desconocido', 0, 2000),
            'proximo_reintento_en' => $proximoReintento,
        ]);

        Log::warning('DeliverWebhookJob: Entrega fallida', [
            'webhook_id' => $this->webhookId,
            'evento' => $this->evento,
            'intento' => $this->intento,
            'max_reintentos' => $this->maxReintentos,
            'status' => $statusCode,
            'puede_reintentar' => $puedeReintentar,
        ]);

        if ($puedeReintentar) {
            self::dispatch(
                $this->webhookId,
                $this->evento,
                $this->payload,
                $this->empresaId,
                $this->intento + 1,
                $this->maxReintentos,
            )->delay($this->calcularBackoff());
        }
    }

    /**
     * Calcula el backoff exponencial en segundos: 30, 120, 480...
     */
    /**
     * @internal Usado en pruebas unitarias
     */
    private function calcularBackoff(): int
    {
        return (int) (30 * pow(4, $this->intento - 1));
    }

    /**
     * Genera firma HMAC-SHA256 del payload.
     */
    /**
     * @internal Usado en pruebas unitarias
     */
    private function generateSignature(string $payloadJson, string $secret): string
    {
        return 'sha256=' . hash_hmac('sha256', $payloadJson, $secret);
    }

    /**
     * Construye el payload completo del webhook.
     *
     * @return array<string, mixed>
     */
    /**
     * @internal Usado en pruebas unitarias
     * @return array{evento: string, timestamp: string, datos: array<string, mixed>}
     */
    private function buildPayload(): array
    {
        return [
            'evento' => $this->evento,
            'timestamp' => now()->toIso8601String(),
            'datos' => $this->payload,
        ];
    }

    /**
     * Valida que la URL destino no apunte a IPs privadas/internas (SSRF protection).
     *
     * v5.0.1: Resuelve hallazgo de auditoría 2026-04-13 (SSRF en webhooks).
     */
    private function isUrlSafe(string $url): bool
    {
        $parsed = parse_url($url);

        if (!$parsed || empty($parsed['host'])) {
            return false;
        }

        $host = $parsed['host'];

        // Bloquear localhost y variantes
        if (in_array(strtolower($host), ['localhost', '0.0.0.0', '[::]', '[::1]'], true)) {
            return false;
        }

        // Resolver el hostname a IP(s)
        $ips = gethostbynamel($host);

        if ($ips === false) {
            // No se pudo resolver — bloquear por precaución
            return false;
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica si una IP pertenece a un rango privado/reservado.
     */
    private function isPrivateIp(string $ip): bool
    {
        foreach (self::BLOCKED_IP_RANGES as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verifica si una IP está dentro de un rango CIDR.
     */
    private function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $bitsStr] = explode('/', $cidr);
        $bits = (int) $bitsStr;

        // Manejar IPv6
        if (str_contains($subnet, ':')) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return false;
            }
            $ipBin = inet_pton($ip);
            $subnetBin = inet_pton($subnet);
            if ($ipBin === false || $subnetBin === false) {
                return false;
            }
            $fullBytes = intdiv($bits, 8);
            $remainderBits = $bits % 8;
            $mask = str_repeat("\xff", $fullBytes) . ($remainderBits ? chr(256 - (1 << (8 - $remainderBits))) : '') . str_repeat("\x00", 16 - (int) ceil($bits / 8));
            return ($ipBin & $mask) === ($subnetBin & $mask);
        }

        // IPv4
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $mask = -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
