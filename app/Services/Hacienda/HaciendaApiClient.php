<?php

namespace App\Services\Hacienda;

use App\Models\FeOAuthToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Cliente HTTP para comunicación con el API de Hacienda Costa Rica
 * 
 * Maneja autenticación OAuth 2.0, rate limiting y reintentos automáticos.
 */
class HaciendaApiClient
{
    /**
     * Cliente HTTP Guzzle
     */
    protected Client $client;

    /**
     * Ambiente actual (sandbox o production)
     */
    protected string $ambiente;

    /**
     * Configuración del ambiente actual
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
    public function __construct(?string $ambiente = null)
    {
        $this->ambiente = $ambiente ?? config('hacienda.environment', 'sandbox');
        $this->config = config("hacienda.api_urls.{$this->ambiente}");
        
        $this->client = new Client([
            'timeout' => config('hacienda.http.timeout', 30),
            'connect_timeout' => config('hacienda.http.connect_timeout', 10),
            'verify' => true,
            'http_errors' => false,
        ]);

        $this->tokenManager = new OAuthTokenManager($this->ambiente);
        $this->rateLimiter = new RateLimiter();
    }

    /**
     * Enviar comprobante electrónico a Hacienda
     * 
     * @param string $clave Clave numérica única de 50 posiciones
     * @param string $xmlFirmado XML firmado digitalmente en base64
     * @param string $fecha Fecha de emisión ISO 8601
     * @param array $emisor Datos del emisor
     * @param array|null $receptor Datos del receptor (opcional)
     * @return array Respuesta de Hacienda
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
     * @return array Estado del comprobante
     * @throws \Exception
     */
    public function consultarEstado(string $clave): array
    {
        return $this->get("/recepcion/{$clave}");
    }

    /**
     * Listar comprobantes enviados
     * 
     * @param array $filtros Filtros de búsqueda (fecha_inicio, fecha_fin, etc.)
     * @return array Lista de comprobantes
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
     * @return array Detalles del comprobante
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
     * @param array $query Parámetros query string
     * @return array Respuesta decodificada
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
     * @param array $data Datos del body
     * @return array Respuesta decodificada
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
     * @param array $options Opciones de Guzzle
     * @return array Respuesta decodificada
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

                // Agregar headers de autenticación
                $options['headers'] = array_merge($options['headers'] ?? [], [
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ]);

                // Log de request
                $this->logRequest($method, $url, $options);

                // Ejecutar request
                $startTime = microtime(true);
                $response = $this->client->request($method, $url, $options);
                $duration = (microtime(true) - $startTime) * 1000;

                // Registrar en rate limiter
                $this->rateLimiter->recordRequest();

                // Procesar respuesta
                $statusCode = $response->getStatusCode();
                $body = (string) $response->getBody();
                $data = json_decode($body, true) ?? [];

                // Log de response
                $this->logResponse($statusCode, $data, $duration);

                // Manejar códigos de estado
                if ($statusCode >= 200 && $statusCode < 300) {
                    return [
                        'success' => true,
                        'status_code' => $statusCode,
                        'data' => $data,
                        'headers' => $this->extractHeaders($response),
                    ];
                }

                // Rate limit excedido
                if ($statusCode === 429) {
                    $retryAfter = $response->getHeader('Retry-After')[0] ?? 60;
                    
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

            } catch (RequestException $e) {
                Log::error('Error en petición HTTP a Hacienda', [
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

                throw new \Exception(
                    "Error en comunicación con API Hacienda después de {$maxAttempts} intentos: " . $e->getMessage(),
                    $e->getCode(),
                    $e
                );

            } catch (GuzzleException $e) {
                Log::error('Error de Guzzle en API Hacienda', [
                    'method' => $method,
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'ambiente' => $this->ambiente,
                ]);

                throw new \Exception(
                    "Error de red con API Hacienda: " . $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        throw new \Exception("No se pudo completar la petición a API Hacienda después de {$maxAttempts} intentos");
    }

    /**
     * Extraer headers relevantes de la respuesta
     */
    protected function extractHeaders($response): array
    {
        return [
            'x_ratelimit_limit' => $response->getHeader('X-Ratelimit-Limit')[0] ?? null,
            'x_ratelimit_remaining' => $response->getHeader('X-Ratelimit-Remaining')[0] ?? null,
            'x_ratelimit_reset' => $response->getHeader('X-Ratelimit-Reset')[0] ?? null,
            'location' => $response->getHeader('Location')[0] ?? null,
        ];
    }

    /**
     * Registrar log de request
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
            throw new \InvalidArgumentException("Ambiente inválido: {$ambiente}");
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
