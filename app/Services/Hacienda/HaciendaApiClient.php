<?php

namespace App\Services\Hacienda;

use App\Exceptions\HaciendaException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;

use App\Models\ConfiguracionApi;

/**
 * Cliente HTTP para comunicación con el API de Hacienda Costa Rica
 *
 * Maneja autenticación OAuth 2.0, rate limiting y reintentos automáticos.
 */
class HaciendaApiClient
{
    /**
     * ID de la empresa (Tenant)
     */
    protected ?int $empresaId;

    /**
     * Ambiente actual (sandbox o production)
     */
    protected string $ambiente;

    /**
     * Configuración del ambiente actual
     *
     * @var array<string, mixed>
     */
    protected array $config;

    /**
     * Servicio de gestión de tokens OAuth
     */
    protected OAuthTokenManager $tokenManager;

    /**
     * Servicio de rate limiting
     */
    protected RateLimiter $rateLimiter;

    /**
     * Constructor
     */
    public function __construct(
        int|string|null $empresaId = null,
        ?string $ambiente = null,
        ?OAuthTokenManager $tokenManager = null,
        ?RateLimiter $rateLimiter = null
    ) {
        if (is_string($empresaId) && in_array($empresaId, ['sandbox', 'production'])) {
            $ambiente = $empresaId;
            $empresaId = null;
        }

        $this->empresaId = $empresaId !== null ? (int) $empresaId : (auth()->user()->empresa_id ?? null);
        $this->ambiente = $ambiente ?? ConfiguracionApi::obtener('hacienda_environment', $this->empresaId, config('hacienda.environment', 'sandbox'));
        $this->config = config("hacienda.api_urls.{$this->ambiente}");
        
        $this->tokenManager = $tokenManager ?? new OAuthTokenManager($this->empresaId, $this->ambiente);
        $this->rateLimiter = $rateLimiter ?? new RateLimiter();
    }

    /**
     * Enviar comprobante electrónico a Hacienda
     *
     * @param string $clave Clave numérica única de 50 posiciones
     * @param string $xmlFirmado XML firmado digitalmente en base64
     * @param string $fecha Fecha de emisión ISO 8601
     * @param array<string, mixed> $emisor Datos del emisor
     * @param array<string, mixed>|null $receptor Datos del receptor (opcional)
     * @return array<string, mixed> Respuesta de Hacienda
     * @throws \Exception
     */
    public function enviarComprobante(
        string $clave,
        string $xmlFirmado,
        string $fecha,
        array $emisor,
        ?array $receptor = null
    ): array {
        $payload = [
            'clave' => $clave,
            'fecha' => $fecha,
            'emisor' => $emisor,
            'comprobanteXml' => $xmlFirmado,
        ];

        if ($receptor) {
            $payload['receptor'] = $receptor;
        }

        return $this->post('/recepcion', $payload);
    }

    /**
     * Consultar estado de un comprobante
     *
     * @param string $clave Clave numérica del comprobante
     * @return array<string, mixed> Estado del comprobante
     * @throws \Exception
     */
    public function consultarEstado(string $clave): array
    {
        return $this->get("/recepcion/{$clave}");
    }

    /**
     * Listar comprobantes enviados
     *
     * @param array<string, mixed> $filtros Filtros de búsqueda (fecha_inicio, fecha_fin, etc.)
     * @return array<string, mixed> Lista de comprobantes
     * @throws \Exception
     */
    public function listarComprobantes(array $filtros = []): array
    {
        return $this->get('/comprobantes', $filtros);
    }

    /**
     * Obtener detalles de un comprobante específico
     *
     * @param string $clave Clave numérica del comprobante
     * @return array<string, mixed> Detalles del comprobante
     * @throws \Exception
     */
    public function obtenerComprobante(string $clave): array
    {
        return $this->get("/comprobantes/{$clave}");
    }

    /**
     * Realizar petición GET
     *
     * @param string $endpoint Endpoint de la API
     * @param array<string, mixed> $query Parámetros query string
     * @return array<string, mixed> Respuesta decodificada
     * @throws \Exception
     */
    protected function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $endpoint, [
            'query' => $query,
        ]);
    }

    /**
     * Realizar petición POST
     *
     * @param string $endpoint Endpoint de la API
     * @param array<string, mixed> $data Datos del body
     * @return array<string, mixed> Respuesta decodificada
     * @throws \Exception
     */
    protected function post(string $endpoint, array $data): array
    {
        return $this->request('POST', $endpoint, [
            'json' => $data,
        ]);
    }

    /**
     * Realizar petición HTTP con reintentos y rate limiting
     *
     * @param string $method Método HTTP
     * @param string $endpoint Endpoint relativo
     * @param array<string, mixed> $options Opciones de Guzzle
     * @return array<string, mixed> Respuesta decodificada
     * @throws \Exception
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        $url = $this->config['recepcion'] . $endpoint;
        $attempts = 0;
        $maxAttempts = config('hacienda.http.retry_times', 3);
        $retryDelay = config('hacienda.http.retry_delay', 1000); // milliseconds

        while ($attempts < $maxAttempts) {
            try {
                // Verificar rate limiting
                $this->rateLimiter->waitIfNeeded();

                // Obtener token OAuth válido
                $token = $this->tokenManager->getValidToken();

                // Log de request
                $this->logRequest($method, $url, $options);

                // Ejecutar request con Laravel Http facade
                $startTime = microtime(true);

                $pendingRequest = Http::timeout(config('hacienda.http.timeout', 30))
                    ->connectTimeout(config('hacienda.http.connect_timeout', 10))
                    ->withToken($token)
                    ->accept('application/json');

                if (strtoupper($method) === 'GET') {
                    $response = $pendingRequest->get($url, $options['query'] ?? []);
                } else {
                    $response = $pendingRequest->asJson()->post($url, $options['json'] ?? []);
                }

                $duration = (microtime(true) - $startTime) * 1000;

                // Registrar en rate limiter
                $this->rateLimiter->recordRequest();

                // Procesar respuesta
                $statusCode = $response->status();
                $data = $response->json() ?? [];

                // Log de response
                $this->logResponse($statusCode, $data, $duration);

                // Manejar códigos de estado
                if ($response->successful()) {
                    return [
                        'success' => true,
                        'status_code' => $statusCode,
                        'data' => $data,
                        'headers' => $this->extractHeaders($response),
                    ];
                }

                // Rate limit excedido
                if ($statusCode === 429) {
                    $retryAfter = $response->header('Retry-After') ?: '60';
                    
                    Log::warning('Rate limit excedido en API Hacienda', [
                        'retry_after' => $retryAfter,
                        'ambiente' => $this->ambiente,
                    ]);

                    if ($attempts < $maxAttempts - 1) {
                        sleep((int) $retryAfter);
                        $attempts++;
                        continue;
                    }
                }

                // Error de autenticación
                if ($statusCode === 401) {
                    Log::warning('Token OAuth inválido, refrescando...', [
                        'ambiente' => $this->ambiente,
                    ]);

                    // Invalidar token actual y obtener uno nuevo
                    $this->tokenManager->refreshToken();
                    
                    if ($attempts < $maxAttempts - 1) {
                        $attempts++;
                        continue;
                    }
                }

                // Otros errores
                return [
                    'success' => false,
                    'status_code' => $statusCode,
                    'error' => $data['message'] ?? $data['error'] ?? 'Error desconocido',
                    'data' => $data,
                ];

            } catch (ConnectionException $e) {
                Log::error('Error de conexión HTTP a Hacienda', [
                    'method' => $method,
                    'url' => $url,
                    'attempt' => $attempts + 1,
                    'error' => $e->getMessage(),
                    'ambiente' => $this->ambiente,
                ]);

                if ($attempts < $maxAttempts - 1) {
                    usleep($retryDelay * 1000);
                    $retryDelay *= 2; // Backoff exponencial
                    $attempts++;
                    continue;
                }

                throw HaciendaException::apiCommunicationError($e->getMessage(), $maxAttempts, $e);

            } catch (RequestException $e) {
                Log::error('Error en petición HTTP a Hacienda', [
                    'method' => $method,
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'ambiente' => $this->ambiente,
                ]);

                throw HaciendaException::networkError($e->getMessage(), $e);
            }
        }

        throw HaciendaException::maxRetriesExceeded($maxAttempts);
    }

    /**
     * Extraer headers relevantes de la respuesta
     *
     * @return array<string, mixed>
     */
    protected function extractHeaders(Response $response): array
    {
        return [
            'x_ratelimit_limit' => $response->header('X-Ratelimit-Limit'),
            'x_ratelimit_remaining' => $response->header('X-Ratelimit-Remaining'),
            'x_ratelimit_reset' => $response->header('X-Ratelimit-Reset'),
            'location' => $response->header('Location'),
        ];
    }

    /**
     * Registrar log de request
     *
     * @param array<string, mixed> $options
     */
    protected function logRequest(string $method, string $url, array $options): void
    {
        if (!config('hacienda.logging.enabled', true)) {
            return;
        }

        Log::channel(config('hacienda.logging.channel', 'daily'))->info('Hacienda API Request', [
            'method' => $method,
            'url' => $url,
            'ambiente' => $this->ambiente,
            'has_body' => isset($options['json']),
        ]);
    }

    /**
     * Registrar log de response
     *
     * @param array<string, mixed> $data
     */
    protected function logResponse(int $statusCode, array $data, float $duration): void
    {
        if (!config('hacienda.logging.enabled', true)) {
            return;
        }

        $level = $statusCode >= 200 && $statusCode < 300 ? 'info' : 'warning';

        Log::channel(config('hacienda.logging.channel', 'daily'))->{$level}('Hacienda API Response', [
            'status_code' => $statusCode,
            'duration_ms' => round($duration, 2),
            'ambiente' => $this->ambiente,
            'success' => $statusCode >= 200 && $statusCode < 300,
        ]);
    }

    /**
     * Cambiar ambiente (sandbox/production)
     */
    public function setAmbiente(string $ambiente): self
    {
        if (!in_array($ambiente, ['sandbox', 'production'])) {
            throw HaciendaException::invalidAmbiente($ambiente);
        }

        $this->ambiente = $ambiente;
        $this->config = config("hacienda.api_urls.{$this->ambiente}");
        $this->tokenManager = new OAuthTokenManager($this->ambiente);

        return $this;
    }

    /**
     * Obtener ambiente actual
     */
    public function getAmbiente(): string
    {
        return $this->ambiente;
    }
}
