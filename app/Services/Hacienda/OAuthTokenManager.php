<?php

namespace App\Services\Hacienda;

use App\Exceptions\HaciendaException;
use App\Models\FeOAuthToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
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
     * URL del endpoint OAuth
     */
    protected string $tokenUrl;

    /**
     * URL del endpoint de Logout
     */
    protected string $logoutUrl;

    /**
     * ID de la empresa (Tenant)
     */
    protected ?int $empresaId;

    /**
     * Credenciales OAuth
     *
     * @var array<string, mixed>
     */
    protected array $credentials;

    /**
     * Constructor
     */
    public function __construct(mixed $empresaIdOrAmbiente = null, ?string $ambiente = null)
    {
        if (is_string($empresaIdOrAmbiente) && in_array($empresaIdOrAmbiente, ['sandbox', 'production'])) {
            $this->empresaId = auth()->user()->empresa_id ?? null;
            $this->ambiente = $empresaIdOrAmbiente;
        } else {
            $this->empresaId = $empresaIdOrAmbiente ?? auth()->user()->empresa_id ?? null;
            $this->ambiente = $ambiente ?? \App\Models\ConfiguracionApi::obtener('hacienda_environment', $this->empresaId, config('hacienda.environment', 'sandbox'));
        }

        $this->tokenUrl = config("hacienda.api_urls.{$this->ambiente}.oauth");
        $this->logoutUrl = config("hacienda.api_urls.{$this->ambiente}.logout", '');

        if ($this->empresaId) {
            $this->credentials = [
                'client_id'     => \App\Models\ConfiguracionApi::obtener('hacienda_oauth_client_id', $this->empresaId, config('hacienda.oauth.client_id', 'api-stag')),
                'client_secret' => \App\Models\ConfiguracionApi::obtener('hacienda_oauth_client_secret', $this->empresaId, config('hacienda.oauth.client_secret', '')),
                'grant_type'    => 'password',
                'username'      => \App\Models\ConfiguracionApi::obtener('hacienda_oauth_username', $this->empresaId, config('hacienda.oauth.username')),
                'password'      => \App\Models\ConfiguracionApi::obtener('hacienda_oauth_password', $this->empresaId, config('hacienda.oauth.password')),
                'scope'         => \App\Models\ConfiguracionApi::obtener('hacienda_oauth_scope', $this->empresaId, config('hacienda.oauth.scope', '')),
            ];
        } else {
            $this->credentials = config('hacienda.oauth');
        }

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
            $formParams = [
                'grant_type' => $this->credentials['grant_type'],
                'client_id' => $this->credentials['client_id'],
                'username' => $this->credentials['username'],
                'password' => $this->credentials['password'],
            ];

            // client_secret y scope son opcionales para Hacienda
            if (!empty($this->credentials['client_secret'])) {
                $formParams['client_secret'] = $this->credentials['client_secret'];
            }
            if (!empty($this->credentials['scope'])) {
                $formParams['scope'] = $this->credentials['scope'];
            }

            $response = Http::timeout(config('hacienda.http.timeout', 30))
                ->connectTimeout(config('hacienda.http.connect_timeout', 10))
                ->asForm()
                ->accept('application/json')
                ->post($this->tokenUrl, $formParams);

            $statusCode = $response->status();
            $data = $response->json();

            if ($statusCode !== 200) {
                Log::error('Error al obtener token OAuth', [
                    'status_code' => $statusCode,
                    'response' => $data,
                    'ambiente' => $this->ambiente,
                ]);

                throw HaciendaException::oauthTokenError(
                    $data['error_description'] ?? $data['error'] ?? 'Error desconocido',
                    $statusCode
                );
            }

            if (!isset($data['access_token'])) {
                throw HaciendaException::oauthMissingAccessToken();
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

        } catch (ConnectionException $e) {
            Log::error('Error de red al obtener token OAuth', [
                'ambiente' => $this->ambiente,
                'error' => $e->getMessage(),
            ]);

            throw HaciendaException::oauthTokenError(
                'Error de conexión con servidor OAuth de Hacienda: ' . $e->getMessage(),
                502
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
     * Refrescar token usando el refresh_token existente
     *
     * Si hay un refresh_token válido, lo usa para obtener un nuevo access_token
     * sin reenviar usuario/contraseña. Si no hay refresh_token o está expirado,
     * inicia una nueva sesión completa.
     *
     * @return string Nuevo access token
     * @throws \Exception
     */
    public function refreshToken(): string
    {
        Log::info('Refrescando token OAuth', [
            'ambiente' => $this->ambiente,
        ]);

        // Buscar token actual con refresh_token
        $tokenActual = FeOAuthToken::ambiente($this->ambiente)
            ->activos()
            ->first();

        if ($tokenActual && !empty($tokenActual->refresh_token)) {
            try {
                $response = Http::timeout(config('hacienda.http.timeout', 30))
                    ->connectTimeout(config('hacienda.http.connect_timeout', 10))
                    ->asForm()
                    ->accept('application/json')
                    ->post($this->tokenUrl, [
                        'grant_type' => 'refresh_token',
                        'client_id' => $this->credentials['client_id'],
                        'refresh_token' => $tokenActual->refresh_token,
                    ]);

                $statusCode = $response->status();
                $data = $response->json();

                if ($statusCode === 200 && isset($data['access_token'])) {
                    // Desactivar token anterior
                    $tokenActual->update(['activo' => false]);

                    $nuevoToken = $this->guardarToken($data);

                    Log::info('Token OAuth refrescado exitosamente', [
                        'ambiente' => $this->ambiente,
                        'token_id' => $nuevoToken->id,
                        'expires_in' => $data['expires_in'] ?? 'unknown',
                    ]);

                    return $nuevoToken->access_token;
                }

                Log::warning('Refresh token inválido o expirado, iniciando nueva sesión', [
                    'ambiente' => $this->ambiente,
                    'status_code' => $statusCode,
                ]);
            } catch (ConnectionException $e) {
                Log::warning('Error al refrescar token, iniciando nueva sesión', [
                    'ambiente' => $this->ambiente,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Marcar token actual como inactivo
        FeOAuthToken::where('ambiente', $this->ambiente)
            ->where('activo', true)
            ->update(['activo' => false]);

        // Obtener nuevo token con credenciales completas
        return $this->obtenerNuevoToken();
    }

    /**
     * Cerrar sesión (Logout) con el servidor OAuth
     *
     * Envía el refresh_token al LOGOUT_URL para invalidar la sesión.
     * Buena práctica recomendada por Hacienda para evitar sesiones huérfanas.
     *
     * @return bool true si el logout fue exitoso
     */
    public function logout(): bool
    {
        if (empty($this->logoutUrl)) {
            Log::warning('URL de logout no configurada', [
                'ambiente' => $this->ambiente,
            ]);
            return false;
        }

        $tokenActual = FeOAuthToken::ambiente($this->ambiente)
            ->activos()
            ->first();

        if (!$tokenActual || empty($tokenActual->refresh_token)) {
            Log::debug('No hay sesión activa para cerrar', [
                'ambiente' => $this->ambiente,
            ]);
            return false;
        }

        try {
            $response = Http::timeout(config('hacienda.http.timeout', 30))
                ->connectTimeout(config('hacienda.http.connect_timeout', 10))
                ->asForm()
                ->post($this->logoutUrl, [
                    'client_id' => $this->credentials['client_id'],
                    'refresh_token' => $tokenActual->refresh_token,
                ]);

            $tokenActual->update(['activo' => false]);

            Log::info('Logout OAuth exitoso', [
                'ambiente' => $this->ambiente,
                'status_code' => $response->status(),
            ]);

            return $response->successful();
        } catch (ConnectionException $e) {
            Log::error('Error al cerrar sesión OAuth', [
                'ambiente' => $this->ambiente,
                'error' => $e->getMessage(),
            ]);

            // Desactivar token local aunque falle el logout remoto
            $tokenActual->update(['activo' => false]);

            return false;
        }
    }

    /**
     * Validar que las credenciales OAuth estén configuradas
     *
     * @throws HaciendaException
     */
    protected function validateCredentials(): void
    {
        if (empty($this->credentials['client_id'])) {
            throw HaciendaException::oauthConfigError(
                'HACIENDA_OAUTH_CLIENT_ID no está configurado en .env. ' .
                'Debe ser "api-stag" para Sandbox o "api-prod" para Producción.'
            );
        }

        if (empty($this->credentials['username'])) {
            throw HaciendaException::oauthConfigError(
                'HACIENDA_OAUTH_USERNAME no está configurado en .env. ' .
                'Genera tus credenciales de pruebas en la Oficina Virtual (OVi) > Tico Factura.'
            );
        }

        if (empty($this->credentials['password'])) {
            throw HaciendaException::oauthConfigError(
                'HACIENDA_OAUTH_PASSWORD no está configurado en .env. ' .
                'Genera tus credenciales de pruebas en la Oficina Virtual (OVi) > Tico Factura.'
            );
        }

        if (empty($this->tokenUrl)) {
            throw HaciendaException::oauthConfigError(
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
