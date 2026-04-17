<?php

namespace Tests\Feature;

use App\Services\Hacienda\HaciendaApiClient;
use App\Services\Hacienda\OAuthTokenManager;
use App\Services\Hacienda\RateLimiter;
use App\Services\Hacienda\ClaveNumericaGenerator;
use App\Services\Hacienda\Xml\FirmaDigitalService;
use App\Services\Hacienda\Xml\XmlComprobanteBuilder;
use App\Services\Hacienda\Xml\XadesEpesSigner;
use App\Models\HaciendaComprobante;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Pruebas de integración: Servicios Hacienda Costa Rica
 *
 * Valida que todos los servicios dedicados existen, tienen las interfaces
 * correctas y están correctamente conectados.
 */
#[CoversClass(\App\Services\Hacienda\HaciendaApiClient::class)]
#[CoversClass(\App\Services\Hacienda\Xml\FirmaDigitalService::class)]
#[CoversClass(\App\Services\Hacienda\Xml\XmlComprobanteBuilder::class)]
#[CoversClass(\App\Http\Controllers\Api\V1\HaciendaController::class)]
class HaciendaIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadHaciendaConfig();
    }

    protected function loadHaciendaConfig(): void
    {
        Config::set('hacienda.environment', 'sandbox');
        Config::set('hacienda.version_esquema', '4.4');
        Config::set('hacienda.proveedor_sistemas', 'TEST SYSTEM');
        Config::set('hacienda.api_urls.sandbox.recepcion', 'https://api-sandbox.comprobanteselectronicos.go.cr/recepcion/v1');
        Config::set('hacienda.api_urls.sandbox.oauth', 'https://idp.comprobanteselectronicos.go.cr/auth/realms/rut-stag/protocol/openid-connect/token');
        Config::set('hacienda.oauth', [
            'grant_type' => 'password',
            'client_id' => 'api-stag',
            'client_secret' => '',
            'username' => 'test@stag.comprobanteselectronicos.go.cr',
            'password' => 'test',
            'scope' => '',
        ]);
        Config::set('hacienda.rate_limit.enabled', true);
        Config::set('hacienda.rate_limit.max_requests_per_second', 8);
        Config::set('hacienda.rate_limit.max_requests_per_minute', 480);
    }

    // ========== SERVICE STRUCTURE TESTS ==========

    public function test_hacienda_services_exist(): void
    {
        $this->assertTrue(class_exists(HaciendaApiClient::class));
        $this->assertTrue(class_exists(OAuthTokenManager::class));
        $this->assertTrue(class_exists(RateLimiter::class));
        $this->assertTrue(class_exists(ClaveNumericaGenerator::class));
        $this->assertTrue(class_exists(FirmaDigitalService::class));
        $this->assertTrue(class_exists(XmlComprobanteBuilder::class));
        $this->assertTrue(class_exists(XadesEpesSigner::class));
    }

    public function test_firma_digital_service_methods(): void
    {
        $this->assertTrue(method_exists(FirmaDigitalService::class, 'firmar'));
        $this->assertTrue(method_exists(FirmaDigitalService::class, 'verificarFirma'));
        $this->assertTrue(method_exists(FirmaDigitalService::class, 'convertirABase64'));
    }

    public function test_hacienda_api_client_methods(): void
    {
        $this->assertTrue(method_exists(HaciendaApiClient::class, 'enviarComprobante'));
        $this->assertTrue(method_exists(HaciendaApiClient::class, 'consultarEstado'));
        $this->assertTrue(method_exists(HaciendaApiClient::class, 'listarComprobantes'));
        $this->assertTrue(method_exists(HaciendaApiClient::class, 'obtenerComprobante'));
        $this->assertTrue(method_exists(HaciendaApiClient::class, 'setAmbiente'));
        $this->assertTrue(method_exists(HaciendaApiClient::class, 'getAmbiente'));
    }

    public function test_oauth_token_manager_methods(): void
    {
        $this->assertTrue(method_exists(OAuthTokenManager::class, 'getValidToken'));
        $this->assertTrue(method_exists(OAuthTokenManager::class, 'obtenerNuevoToken'));
        $this->assertTrue(method_exists(OAuthTokenManager::class, 'refreshToken'));
        $this->assertTrue(method_exists(OAuthTokenManager::class, 'logout'));
    }

    public function test_clave_numerica_generator_methods(): void
    {
        $this->assertTrue(method_exists(ClaveNumericaGenerator::class, 'generar'));
        $this->assertTrue(method_exists(ClaveNumericaGenerator::class, 'validar'));
        $this->assertTrue(method_exists(ClaveNumericaGenerator::class, 'extraerInformacion'));
    }

    public function test_xml_comprobante_builder_methods(): void
    {
        $this->assertTrue(method_exists(XmlComprobanteBuilder::class, 'build'));
    }

    public function test_hacienda_config_loaded(): void
    {
        $this->assertEquals('sandbox', config('hacienda.environment'));
        $this->assertEquals('4.4', config('hacienda.version_esquema'));
        $this->assertNotNull(config('hacienda.api_urls.sandbox'));
    }

    // ========== RATE LIMITER TESTS ==========

    public function test_rate_limiter_allows_requests(): void
    {
        $rateLimiter = new RateLimiter();
        $this->assertTrue($rateLimiter->canMakeRequest());
    }

    public function test_rate_limiter_tracks_requests(): void
    {
        $rateLimiter = new RateLimiter();
        $rateLimiter->recordRequest();
        
        $stats = $rateLimiter->getEstadisticas();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('current_second', $stats);
        $this->assertArrayHasKey('current_minute', $stats);
        $this->assertGreaterThanOrEqual(1, $stats['current_second']['requests']);
    }

    // ========== MODEL TESTS ==========

    public function test_hacienda_model_structure(): void
    {
        $this->assertTrue(class_exists(HaciendaComprobante::class));
        
        $hacienda = new HaciendaComprobante();
        $fillable = $hacienda->getFillable();

        $this->assertContains('comprobante_id', $fillable);
        $this->assertContains('empresa_id', $fillable);
        $this->assertContains('clave', $fillable);
        $this->assertContains('tipo_comprobante', $fillable);
        $this->assertContains('estado', $fillable);
    }

    public function test_hacienda_model_has_state_methods(): void
    {
        $this->assertTrue(method_exists(HaciendaComprobante::class, 'markAsSigned'));
        $this->assertTrue(method_exists(HaciendaComprobante::class, 'markAsSent'));
        $this->assertTrue(method_exists(HaciendaComprobante::class, 'markAsAccepted'));
        $this->assertTrue(method_exists(HaciendaComprobante::class, 'markAsRejected'));
        $this->assertTrue(method_exists(HaciendaComprobante::class, 'markAsError'));
        $this->assertTrue(method_exists(HaciendaComprobante::class, 'isReadyForSending'));
    }

    // ========== CONTROLLER TESTS ==========

    public function test_hacienda_controller_exists(): void
    {
        $this->assertTrue(class_exists('App\Http\Controllers\Api\V1\HaciendaController'));
    }

    public function test_hacienda_controller_uses_di(): void
    {
        $reflection = new \ReflectionClass('App\Http\Controllers\Api\V1\HaciendaController');
        $constructor = $reflection->getConstructor();
        
        $this->assertNotNull($constructor, 'HaciendaController debe tener constructor con DI');
        $this->assertGreaterThanOrEqual(3, $constructor->getNumberOfParameters());
    }
}
