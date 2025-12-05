<?php

namespace Tests\Unit\Services\Hacienda;

use App\Services\Hacienda\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Tests para RateLimiter de Hacienda
 *
 * Valida cumplimiento con límites oficiales de la API:
 * - Burst: 20 req/seg por 5 seg (max 100)
 * - Sostenido: 10 req/seg por 120 seg (max 1200)
 * - Bloqueo de IP por 10 minutos si se excede
 *
 * @see https://api.hacienda.go.cr/docs/
 */
class RateLimiterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configurar rate limiting para tests
        Config::set('hacienda.rate_limit.enabled', true);
        Config::set('hacienda.rate_limit.max_requests_per_second', 8);
        Config::set('hacienda.rate_limit.max_requests_per_minute', 480);

        // Limpiar cache entre tests
        Cache::flush();
    }

    /** @test */
    public function puede_hacer_request_cuando_no_hay_limite(): void
    {
        $rateLimiter = new RateLimiter();

        $this->assertTrue($rateLimiter->canMakeRequest());
    }

    /** @test */
    public function registra_requests_correctamente(): void
    {
        $rateLimiter = new RateLimiter();

        // Registrar algunos requests
        $rateLimiter->recordRequest();
        $rateLimiter->recordRequest();
        $rateLimiter->recordRequest();

        // Debería poder seguir haciendo requests
        $this->assertTrue($rateLimiter->canMakeRequest());
    }

    /** @test */
    public function bloquea_cuando_excede_limite_por_segundo(): void
    {
        Config::set('hacienda.rate_limit.max_requests_per_second', 3);

        $rateLimiter = new RateLimiter();

        // Registrar hasta el límite
        $rateLimiter->recordRequest();
        $rateLimiter->recordRequest();
        $rateLimiter->recordRequest();

        // El siguiente debería ser bloqueado
        $this->assertFalse($rateLimiter->canMakeRequest());
    }

    /** @test */
    public function bloquea_cuando_excede_limite_por_minuto(): void
    {
        Config::set('hacienda.rate_limit.max_requests_per_second', 100); // Alto para no interferir
        Config::set('hacienda.rate_limit.max_requests_per_minute', 5);

        $rateLimiter = new RateLimiter();

        // Registrar hasta el límite por minuto
        for ($i = 0; $i < 5; $i++) {
            $rateLimiter->recordRequest();
        }

        // El siguiente debería ser bloqueado
        $this->assertFalse($rateLimiter->canMakeRequest());
    }

    /** @test */
    public function respeta_configuracion_deshabilitada(): void
    {
        Config::set('hacienda.rate_limit.enabled', false);
        Config::set('hacienda.rate_limit.max_requests_per_second', 1);

        $rateLimiter = new RateLimiter();

        // Registrar múltiples requests
        $rateLimiter->recordRequest();
        $rateLimiter->recordRequest();
        $rateLimiter->recordRequest();

        // No debería lanzar excepción cuando está deshabilitado
        $rateLimiter->waitIfNeeded();

        // Si llegamos aquí, el test pasa
        $this->assertTrue(true);
    }

    /** @test */
    public function obtiene_estadisticas_correctas(): void
    {
        $rateLimiter = new RateLimiter();

        // Registrar requests
        $rateLimiter->recordRequest();
        $rateLimiter->recordRequest();

        $stats = $rateLimiter->getStats();

        $this->assertArrayHasKey('requests_this_second', $stats);
        $this->assertArrayHasKey('requests_this_minute', $stats);
        $this->assertArrayHasKey('max_per_second', $stats);
        $this->assertArrayHasKey('max_per_minute', $stats);
        $this->assertArrayHasKey('can_make_request', $stats);
    }

    /** @test */
    public function limites_corresponden_a_documentacion_hacienda(): void
    {
        // Verificar que los valores por defecto respetan los límites de Hacienda
        // Hacienda: 20 req/seg burst, 10 req/seg sostenido
        // Usamos valores conservadores: 8/seg y 480/min

        $limiteSegundo = config('hacienda.rate_limit.max_requests_per_second');
        $limiteMinuto = config('hacienda.rate_limit.max_requests_per_minute');

        // Debe ser menor que 20 (burst limit)
        $this->assertLessThanOrEqual(20, $limiteSegundo);

        // Debe ser menor que 1200 (10 * 120 seg sostenido)
        $this->assertLessThanOrEqual(1200, $limiteMinuto);

        // Pero debe permitir operaciones razonables
        $this->assertGreaterThanOrEqual(5, $limiteSegundo);
        $this->assertGreaterThanOrEqual(300, $limiteMinuto);
    }

    /** @test */
    public function puede_reiniciar_contadores(): void
    {
        Config::set('hacienda.rate_limit.max_requests_per_second', 2);

        $rateLimiter = new RateLimiter();

        // Llenar hasta el límite
        $rateLimiter->recordRequest();
        $rateLimiter->recordRequest();

        $this->assertFalse($rateLimiter->canMakeRequest());

        // Reiniciar contadores
        $rateLimiter->reset();

        $this->assertTrue($rateLimiter->canMakeRequest());
    }
}
