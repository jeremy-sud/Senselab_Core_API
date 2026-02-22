<?php

namespace App\Services\Hacienda;

use App\Models\FeOAuthToken;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Gestor de Tokens OAuth 2.0 para API de Hacienda
 * 
 * Maneja obtención, almacenamiento y refresco automático de tokens.
 */
class OAuthTokenManager
{
    /**
     * Ambiente actual (sandbox o production)
     */
    protected string $ambiente;

    /**
     * Cliente HTTP
     */
    protected Client $client;

    /**
     * URL del endpoint OAuth
     */
    protected string $tokenUrl;

    /**
     * Credenciales OAuth
     * 
     * @var array<string, mixed>
     */
    protected array $credentials;

    /**
     * Constructor
     */
    public function __construct(string $ambiente = 'sandbox')
    {
        $this->ambiente = $ambiente;
        $this->tokenUrl = config("hacienda.api_urls.{$ambiente}.oauth");
        $this->credentials = config('hacienda.oauth');

        $this->client = new Client([
            'timeout' => 30,
            'verify' => true,
            'http_errors' => false,
        ]);

        $this->validateCredentials();
    }

    /**
     * Obtener un token válido (existente o nuevo)
     * 
     * @return string Access token
     * @throws \Exception
     */
    public function getValidToken(): string
    {
        // Buscar token válido en BD
        $token = FeOAuthToken::ambiente($this->ambiente)
            ->activos()
            ->validos()
            ->orderByDesc('created_at')
            ->first();

        // Si existe y no está próximo a expirar, usarlo
        if ($token && !$token->proximo_expirar) {
            $token->incrementarUso();
            
            Log::debug('Usando token OAuth existente', [
                'ambiente' => $this->ambiente,
                'token_id' => $token->id,
                'expires_in' => $token->segundos_restantes . 's',
            ]);

            return $token->access_token;
        }

        // Obtener nuevo token
        return $this->obtenerNuevoToken();
    }

    /**
     * Obtener un nuevo token desde el servidor OAuth
     * 
     * @return string Access token
     * @throws \Exception
     */
    public function obtenerNuevoToken(): string
    {
        Log::info('Solicitando nuevo token OAuth a Hacienda', [
            'ambiente' => $this->ambiente,
            'token_url' => $this->tokenUrl,
        ]);

        try {
            $response = $this->client->post($this->tokenUrl, [
                'form_params' => [
                    'grant_type' => $this->credentials['grant_type'],
                    'client_id' => $this->credentials['client_id'],
                    'client_secret' => $this->credentials['client_secret'],
                    'scope' => $this->credentials['scope'] ?? '',
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Accept' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = (string) $response->getBody();
            $data = json_decode($body, true);

            if ($statusCode !== 200) {
                Log::error('Error al obtener token OAuth', [
                    'status_code' => $statusCode,
                    'response' => $data,
                    'ambiente' => $this->ambiente,
                ]);

                throw new \Exception(
                    "Error al obtener token OAuth: " . ($data['error_description'] ?? $data['error'] ?? 'Error desconocido'),
                    $statusCode
                );
            }

            if (!isset($data['access_token'])) {
                throw new \Exception('Respuesta OAuth no contiene access_token');
            }

            // Guardar token en BD
            $tokenModel = $this->guardarToken($data);

            Log::info('Token OAuth obtenido exitosamente', [
                'ambiente' => $this->ambiente,
                'token_id' => $tokenModel->id,
                'expires_in' => $data['expires_in'] ?? 'unknown',
                'token_type' => $data['token_type'] ?? 'Bearer',
            ]);

            return $tokenModel->access_token;

        } catch (GuzzleException $e) {
            Log::error('Error de red al obtener token OAuth', [
                'ambiente' => $this->ambiente,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception(
                "Error de conexión con servidor OAuth de Hacienda: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Guardar token en la base de datos
     * 
     * @param array<string, mixed> $data
     */
    protected function guardarToken(array $data): FeOAuthToken
    {
        // Desactivar tokens anteriores del mismo ambiente
        FeOAuthToken::where('ambiente', $this->ambiente)
            ->where('activo', true)
            ->update(['activo' => false]);

        // Calcular timestamp de expiración
        $expiresIn = $data['expires_in'] ?? 3600;
        $expiresAt = Carbon::now()->addSeconds($expiresIn);

        // Crear nuevo token
        return FeOAuthToken::create([
            'ambiente' => $this->ambiente,
            'access_token' => $data['access_token'],
            'token_type' => $data['token_type'] ?? 'Bearer',
            'expires_in' => $expiresIn,
            'expires_at' => $expiresAt,
            'refresh_token' => $data['refresh_token'] ?? null,
            'scope' => $data['scope'] ?? null,
            'activo' => true,
            'uso_contador' => 0,
            'metadata' => [
                'issued_at' => Carbon::now()->toIso8601String(),
                'token_url' => $this->tokenUrl,
            ],
        ]);
    }

    /**
     * Refrescar token (invalidar el actual y obtener uno nuevo)
     * 
     * @return string Nuevo access token
     * @throws \Exception
     */
    public function refreshToken(): string
    {
        Log::info('Refrescando token OAuth', [
            'ambiente' => $this->ambiente,
        ]);

        // Marcar token actual como inactivo
        FeOAuthToken::where('ambiente', $this->ambiente)
            ->where('activo', true)
            ->update(['activo' => false]);

        // Obtener nuevo token
        return $this->obtenerNuevoToken();
    }

    /**
     * Validar que las credenciales OAuth estén configuradas
     * 
     * @throws \Exception
     */
    protected function validateCredentials(): void
    {
        if (empty($this->credentials['client_id'])) {
            throw new \Exception(
                'HACIENDA_OAUTH_CLIENT_ID no está configurado en .env. ' .
                'Debes obtener las credenciales OAuth del portal de Hacienda.'
            );
        }

        if (empty($this->credentials['client_secret'])) {
            throw new \Exception(
                'HACIENDA_OAUTH_CLIENT_SECRET no está configurado en .env. ' .
                'Debes obtener las credenciales OAuth del portal de Hacienda.'
            );
        }

        if (empty($this->tokenUrl)) {
            throw new \Exception(
                "URL de OAuth para ambiente {$this->ambiente} no está configurada."
            );
        }
    }

    /**
     * Limpiar tokens expirados del ambiente actual
     * 
     * @return int Cantidad de tokens eliminados
     */
    public function limpiarTokensExpirados(): int
    {
        $count = FeOAuthToken::where('ambiente', $this->ambiente)
            ->where('expires_at', '<', Carbon::now()->subDays(7))
            ->delete();

        if ($count > 0) {
            Log::info('Tokens expirados limpiados', [
                'ambiente' => $this->ambiente,
                'count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Obtener estadísticas de uso de tokens
     * 
     * @return array<string, mixed> Estadísticas
     */
    public function getEstadisticas(): array
    {
        $tokenActual = FeOAuthToken::ambiente($this->ambiente)
            ->activos()
            ->first();

        $totalTokens = FeOAuthToken::where('ambiente', $this->ambiente)->count();
        $tokensActivos = FeOAuthToken::ambiente($this->ambiente)->activos()->count();
        $tokensValidos = FeOAuthToken::ambiente($this->ambiente)->validos()->count();

        return [
            'ambiente' => $this->ambiente,
            'token_actual' => $tokenActual ? [
                'id' => $tokenActual->id,
                'expires_at' => $tokenActual->expires_at->toIso8601String(),
                'segundos_restantes' => $tokenActual->segundos_restantes,
                'uso_contador' => $tokenActual->uso_contador,
                'ultimo_uso' => $tokenActual->ultimo_uso?->toIso8601String(),
            ] : null,
            'total_tokens_historico' => $totalTokens,
            'tokens_activos' => $tokensActivos,
            'tokens_validos' => $tokensValidos,
        ];
    }
}
