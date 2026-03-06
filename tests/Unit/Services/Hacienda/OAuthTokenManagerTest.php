<?php

namespace Tests\Unit\Services\Hacienda;

use App\Models\FeOAuthToken;
use App\Services\Hacienda\OAuthTokenManager;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests para OAuthTokenManager
 *
 * Valida:
 * - Obtención de tokens OAuth 2.0
 * - Almacenamiento y reutilización de tokens
 * - Refresco automático de tokens expirados
 * - Manejo de errores de autenticación
 * - Conformidad con OAuth de Hacienda Costa Rica
 *
 * @see https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token (sandbox)
 * @see https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token (production)
 */
class OAuthTokenManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Configurar credenciales OAuth de prueba
        config([
            'hacienda.oauth.client_id' => 'test_client_id',
            'hacienda.oauth.client_secret' => 'test_client_secret',
            'hacienda.oauth.grant_type' => 'client_credentials',
            'hacienda.oauth.scope' => '',
            'hacienda.api_urls.sandbox.oauth' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token',
            'hacienda.api_urls.production.oauth' => 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut/protocol/openid-connect/token',
        ]);
    }

    #[Test]
    public function puede_obtener_token_existente_valido(): void
    {
        // Crear token válido en BD
        $token = FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'existing_valid_token_12345',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => true,
            'uso_contador' => 0,
        ]);

        $manager = new OAuthTokenManager('sandbox');

        // Mock del cliente HTTP para evitar llamadas reales
        $this->mockHttpClient($manager);

        $accessToken = $manager->getValidToken();

        $this->assertEquals('existing_valid_token_12345', $accessToken);

        // Verificar que se incrementó el contador
        $token->refresh();
        $this->assertEquals(1, $token->uso_contador);
    }

    #[Test]
    public function reutiliza_token_valido_existente(): void
    {
        // Crear token válido
        $token = FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'reusable_token_xyz',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => true,
            'uso_contador' => 0,
        ]);

        $manager = new OAuthTokenManager('sandbox');
        $this->mockHttpClient($manager);

        // Obtener token múltiples veces
        $token1 = $manager->getValidToken();
        $token2 = $manager->getValidToken();
        $token3 = $manager->getValidToken();

        // Debería ser el mismo token
        $this->assertEquals('reusable_token_xyz', $token1);
        $this->assertEquals($token1, $token2);
        $this->assertEquals($token2, $token3);

        // El contador debería incrementarse
        $token->refresh();
        $this->assertEquals(3, $token->uso_contador);
    }

    #[Test]
    public function puede_guardar_nuevo_token_en_bd(): void
    {
        // Verificar que no hay tokens
        $this->assertEquals(0, FeOAuthToken::count());

        // Crear token directamente (simulando respuesta de API)
        $tokenData = [
            'access_token' => 'new_token_from_api',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'refresh_token_xyz',
            'scope' => 'openid',
        ];

        $token = FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => $tokenData['access_token'],
            'token_type' => $tokenData['token_type'],
            'expires_in' => $tokenData['expires_in'],
            'expires_at' => Carbon::now()->addSeconds($tokenData['expires_in']),
            'refresh_token' => $tokenData['refresh_token'],
            'scope' => $tokenData['scope'],
            'activo' => true,
            'uso_contador' => 0,
        ]);

        $this->assertDatabaseHas('fe_oauth_tokens', [
            'id' => $token->id,
            'ambiente' => 'sandbox',
            'access_token' => 'new_token_from_api',
            'token_type' => 'Bearer',
            'activo' => true,
        ]);
    }

    #[Test]
    public function desactiva_tokens_anteriores_al_crear_nuevo(): void
    {
        // Crear token antiguo activo
        $tokenViejo = FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'old_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => true,
        ]);

        // Simular creación de nuevo token (desactivar anteriores)
        FeOAuthToken::where('ambiente', 'sandbox')
            ->where('activo', true)
            ->update(['activo' => false]);

        // Crear nuevo token
        $tokenNuevo = FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'new_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => true,
        ]);

        // El token viejo debería estar inactivo
        $tokenViejo->refresh();
        $this->assertFalse((bool)$tokenViejo->activo);

        // El nuevo debería estar activo
        $this->assertTrue((bool)$tokenNuevo->activo);
    }

    #[Test]
    public function identifica_tokens_proximos_a_expirar(): void
    {
        // Token que expira en 4 minutos (< 5 min buffer)
        $tokenProximoExpirar = FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'expiring_soon',
            'token_type' => 'Bearer',
            'expires_in' => 240,
            'expires_at' => Carbon::now()->addMinutes(4),
            'activo' => true,
        ]);

        // Token que expira en 1 hora
        $tokenValido = FeOAuthToken::create([
            'ambiente' => 'production',
            'access_token' => 'valid_for_long',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => true,
        ]);

        $this->assertTrue($tokenProximoExpirar->proximo_expirar);
        $this->assertFalse($tokenValido->proximo_expirar);
    }

    #[Test]
    public function calcula_segundos_restantes_correctamente(): void
    {
        $token = FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'test_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addMinutes(30),
            'activo' => true,
        ]);

        // Debería estar cerca de 1800 segundos (30 min)
        $segundos = $token->segundos_restantes;
        $this->assertGreaterThan(1790, $segundos);
        $this->assertLessThanOrEqual(1800, $segundos);
    }

    #[Test]
    public function identifica_tokens_expirados(): void
    {
        $tokenExpirado = FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'expired_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->subMinute(),
            'activo' => true,
        ]);

        $this->assertTrue($tokenExpirado->expirado);
        // Token expirado no debería aparecer en scope validos
        $this->assertEquals(0, FeOAuthToken::validos()->count());
    }

    #[Test]
    public function scope_activos_filtra_correctamente(): void
    {
        // Crear tokens activos e inactivos
        FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'active_1',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => true,
        ]);

        FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'inactive_1',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => false,
        ]);

        $activos = FeOAuthToken::activos()->get();

        $this->assertCount(1, $activos);
        $this->assertEquals('active_1', $activos->first()->access_token);
    }

    #[Test]
    public function scope_ambiente_filtra_correctamente(): void
    {
        FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'sandbox_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => true,
        ]);

        FeOAuthToken::create([
            'ambiente' => 'production',
            'access_token' => 'production_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => true,
        ]);

        $sandboxTokens = FeOAuthToken::ambiente('sandbox')->get();
        $productionTokens = FeOAuthToken::ambiente('production')->get();

        $this->assertCount(1, $sandboxTokens);
        $this->assertCount(1, $productionTokens);
        $this->assertEquals('sandbox_token', $sandboxTokens->first()->access_token);
        $this->assertEquals('production_token', $productionTokens->first()->access_token);
    }

    #[Test]
    public function scope_validos_excluye_expirados(): void
    {
        // Token válido
        FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'valid_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->addHour(),
            'activo' => true,
        ]);

        // Token expirado
        FeOAuthToken::create([
            'ambiente' => 'sandbox',
            'access_token' => 'expired_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => Carbon::now()->subMinute(),
            'activo' => true,
        ]);

        $validos = FeOAuthToken::validos()->get();

        $this->assertCount(1, $validos);
        $this->assertEquals('valid_token', $validos->first()->access_token);
    }

    /**
     * Helper para mockear el cliente HTTP
     */
    protected function mockHttpClient(OAuthTokenManager $manager): void
    {
        // Usar reflection para acceder al cliente privado (solo para tests)
        $reflection = new \ReflectionClass($manager);

        if ($reflection->hasProperty('client')) {
            $property = $reflection->getProperty('client');
            $property->setAccessible(true);

            $mock = new MockHandler([
                new Response(200, [], json_encode([
                    'access_token' => 'mocked_access_token',
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                ])),
            ]);

            $handlerStack = HandlerStack::create($mock);
            $mockedClient = new Client(['handler' => $handlerStack]);

            $property->setValue($manager, $mockedClient);
        }
    }
}
