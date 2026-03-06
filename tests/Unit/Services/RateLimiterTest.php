<?php

namespace Tests\Unit\Services;

use App\Services\Hacienda\RateLimiter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class RateLimiterTest extends TestCase
{
    protected RateLimiter $rateLimiter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rateLimiter = new RateLimiter();
        Cache::flush();
    }

    #[Test]
    public function puede_hacer_request_cuando_no_hay_limite()
    {
        $this->assertTrue($this->rateLimiter->canMakeRequest());
    }

    #[Test]
    public function registra_request_correctamente()
    {
        $this->rateLimiter->recordRequest();
        
        $estadisticas = $this->rateLimiter->getEstadisticas();
        
        $this->assertEquals(1, $estadisticas['current_second']['requests']);
        $this->assertEquals(1, $estadisticas['current_minute']['requests']);
    }

    #[Test]
    public function respeta_limite_por_segundo()
    {
        Config::set('hacienda.rate_limit.max_requests_per_second', 2);
        $limiter = new RateLimiter();
        
        $limiter->recordRequest();
        $limiter->recordRequest();
        
        $this->assertFalse($limiter->canMakeRequest());
    }

    #[Test]
    public function respeta_limite_por_minuto()
    {
        Config::set('hacienda.rate_limit.max_requests_per_minute', 2);
        $limiter = new RateLimiter();
        
        $limiter->recordRequest();
        $limiter->recordRequest();
        
        $this->assertFalse($limiter->canMakeRequest());
    }

    #[Test]
    public function puede_resetear_contadores()
    {
        $this->rateLimiter->recordRequest();
        $this->rateLimiter->reset();
        
        $estadisticas = $this->rateLimiter->getEstadisticas();
        
        $this->assertEquals(0, $estadisticas['current_second']['requests']);
    }

    #[Test]
    public function obtiene_estadisticas_correctamente()
    {
        $estadisticas = $this->rateLimiter->getEstadisticas();
        
        $this->assertArrayHasKey('enabled', $estadisticas);
        $this->assertArrayHasKey('current_second', $estadisticas);
        $this->assertArrayHasKey('current_minute', $estadisticas);
        $this->assertArrayHasKey('can_make_request', $estadisticas);
    }

    #[Test]
    public function obtiene_configuracion_correctamente()
    {
        $config = $this->rateLimiter->getConfiguracion();
        
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('max_requests_per_second', $config);
        $this->assertArrayHasKey('max_requests_per_minute', $config);
    }

    #[Test]
    public function calcula_disponibilidad_correctamente()
    {
        Config::set('hacienda.rate_limit.max_requests_per_second', 10);
        $limiter = new RateLimiter();
        
        $limiter->recordRequest();
        $limiter->recordRequest();
        
        $estadisticas = $limiter->getEstadisticas();
        
        $this->assertEquals(8, $estadisticas['current_second']['available']);
    }

    #[Test]
    public function calcula_porcentaje_uso_correctamente()
    {
        Config::set('hacienda.rate_limit.max_requests_per_second', 10);
        $limiter = new RateLimiter();
        
        $limiter->recordRequest();
        $limiter->recordRequest();
        
        $estadisticas = $limiter->getEstadisticas();
        
        $this->assertEquals(20.0, $estadisticas['current_second']['percentage_used']);
    }

    #[Test]
    public function no_registra_request_cuando_esta_deshabilitado()
    {
        Config::set('hacienda.rate_limit.enabled', false);
        $limiter = new RateLimiter();
        
        $limiter->recordRequest();
        $estadisticas = $limiter->getEstadisticas();
        
        $this->assertEquals(0, $estadisticas['current_second']['requests']);
    }
}
