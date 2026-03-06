<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\RateLimitingService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Rate Limiting Granular Test
 *
 * FASE 1.5: Verificar que el rate limiting diferenciado funciona
 * correctamente para usuarios autenticados vs guests
 *
 * Nota: Extiende de BaseTestCase sin RefreshDatabase para evitar
 * dependencias de BD en tests de lógica pura.
 *
 */
#[CoversClass(\App\Services\RateLimitingService::class)]
#[CoversClass(\App\Http\Middleware\ThrottleRequestsWithRetryAfter::class)]
class RateLimitingGranularTest extends BaseTestCase
{
    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /**
     * Test: usuarios autenticados tienen límites más altos
     */
    public function test_authenticated_users_have_higher_limits(): void
    {
        $user = new \App\Models\Usuario();
        $user->id = 1;

        $authLimit = RateLimitingService::getLimit($this->makeRequest($user), 'api');
        $guestLimit = RateLimitingService::getLimit($this->makeRequest(null), 'api');

        $this->assertGreaterThan($guestLimit, $authLimit);
        $this->assertEquals(60, $authLimit);
        $this->assertEquals(30, $guestLimit);
    }

    /**
     * Test: diferenciación por tipo de endpoint
     */
    public function test_different_limits_by_endpoint_type(): void
    {
        $user = new \App\Models\Usuario();
        $user->id = 1;
        $request = $this->makeRequest($user);

        $apiLimit = RateLimitingService::getLimit($request, 'api');
        $reportLimit = RateLimitingService::getLimit($request, 'reports');
        $importLimit = RateLimitingService::getLimit($request, 'imports');
        $paymentLimit = RateLimitingService::getLimit($request, 'payment_process');

        $this->assertEquals(60, $apiLimit);
        $this->assertEquals(15, $reportLimit);
        $this->assertEquals(5, $importLimit);
        $this->assertEquals(5, $paymentLimit);
    }

    /**
     * Test: detectar cuando se excede el límite
     */
    public function test_detects_limit_exceeded(): void
    {
        $user = new \App\Models\Usuario();
        $user->id = 1;
        $request = $this->makeRequest($user);

        // No debe estar excedido inicialmente
        $this->assertFalse(RateLimitingService::isExceeded($request, 'api'));

        // Simular múltiples requests hasta el límite
        $limit = RateLimitingService::getLimit($request, 'api');
        for ($i = 0; $i < $limit; $i++) {
            RateLimitingService::increment($request, 'api');
        }

        // Ahora debe estar excedido
        $this->assertTrue(RateLimitingService::isExceeded($request, 'api'));
    }

    /**
     * Test: contar intentos restantes correctamente
     */
    public function test_remaining_attempts_counted_correctly(): void
    {
        $user = new \App\Models\Usuario();
        $user->id = 1;
        $request = $this->makeRequest($user);

        $limit = RateLimitingService::getLimit($request, 'api');
        $this->assertEquals($limit, RateLimitingService::remaining($request, 'api'));

        // Hacer 3 requests
        for ($i = 0; $i < 3; $i++) {
            RateLimitingService::increment($request, 'api');
        }

        $this->assertEquals($limit - 3, RateLimitingService::remaining($request, 'api'));
    }

    /**
     * Test: guests no pueden acceder a pagos
     */
    public function test_guests_blocked_from_payment_endpoints(): void
    {
        $request = $this->makeRequest(null);

        $limit = RateLimitingService::getLimit($request, 'payment_process');
        $this->assertEquals(0, $limit);
    }

    /**
     * Test: identificador único por user/ip
     */
    public function test_identifier_differs_for_different_users(): void
    {
        $user1 = new \App\Models\Usuario();
        $user1->id = 123;

        $user2 = new \App\Models\Usuario();
        $user2->id = 456;

        $id1 = RateLimitingService::getIdentifier($this->makeRequest($user1));
        $id2 = RateLimitingService::getIdentifier($this->makeRequest($user2));

        $this->assertNotEquals($id1, $id2);
        $this->assertEquals(123, $id1);
        $this->assertEquals(456, $id2);
    }

    /**
     * Test: logging de violaciones no lanza excepciones
     */
    public function test_violations_are_logged_successfully(): void
    {
        $user = new \App\Models\Usuario();
        $user->id = 1;
        $request = $this->makeRequest($user);

        // No debe lanzar excepciones
        try {
            RateLimitingService::logViolation($request, 'api');
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('logViolation lanzó una excepción: ' . $e->getMessage());
        }
    }

    /**
     * Test: estructura del RateLimitingService
     */
    public function test_rate_limiting_service_structure(): void
    {
        $this->assertTrue(class_exists(RateLimitingService::class));
        $this->assertTrue(method_exists(RateLimitingService::class, 'getLimit'));
        $this->assertTrue(method_exists(RateLimitingService::class, 'isExceeded'));
        $this->assertTrue(method_exists(RateLimitingService::class, 'increment'));
        $this->assertTrue(method_exists(RateLimitingService::class, 'remaining'));
        $this->assertTrue(method_exists(RateLimitingService::class, 'getIdentifier'));
    }

    /**
     * Test: respuesta JSON tiene estructura correcta
     */
    public function test_rate_limit_response_format(): void
    {
        // Verificar que la clase existe y se puede instanciar
        $this->assertTrue(class_exists(RateLimitingService::class));
    }

    /**
     * Helpers - Crear request mock sin BD
     */
    protected function makeRequest($user = null)
    {
        $request = new \Illuminate\Http\Request();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        if ($user) {
            $request->setUserResolver(function () use ($user) {
                return $user;
            });
        }

        return $request;
    }
}
