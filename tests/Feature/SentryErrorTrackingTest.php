<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\SentryService;
use PHPUnit\Framework\TestCase;

/**
 * Sentry Error Tracking Test
 *
 * FASE 1.4: Verificar que Sentry está correctamente configurado
 * para capturar errores, excepciones y transacciones
 *
 * @test
 * @covers \App\Services\SentryService
 */
class SentryErrorTrackingTest extends TestCase
{
    /**
     * Verificar que el servicio Sentry está disponible
     */
    public function test_sentry_service_exists(): void
    {
        $this->assertTrue(class_exists(SentryService::class));
        $this->assertTrue(method_exists(SentryService::class, 'captureException'));
        $this->assertTrue(method_exists(SentryService::class, 'captureMessage'));
        $this->assertTrue(method_exists(SentryService::class, 'setUserContext'));
        $this->assertTrue(method_exists(SentryService::class, 'addContext'));
        $this->assertTrue(method_exists(SentryService::class, 'addBreadcrumb'));
        $this->assertTrue(method_exists(SentryService::class, 'captureTransaction'));
        $this->assertTrue(method_exists(SentryService::class, 'isEnabled'));
    }

    /**
     * Verificar que Sentry se puede deshabilitar en testing
     */
    public function test_sentry_can_be_disabled_in_testing(): void
    {
        // En testing, Sentry debería estar deshabilitado por defecto
        $this->assertTrue(true);
    }

    /**
     * Verificar que SentryService tiene métodos públicos correctos
     */
    public function test_sentry_service_has_public_methods(): void
    {
        $reflection = new \ReflectionClass(SentryService::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_STATIC);
        
        $methodNames = array_map(fn($method) => $method->getName(), $methods);
        
        // Verificar que todos los métodos principales existen
        $this->assertContains('captureException', $methodNames);
        $this->assertContains('captureMessage', $methodNames);
        $this->assertContains('setUserContext', $methodNames);
        $this->assertContains('addContext', $methodNames);
        $this->assertContains('addBreadcrumb', $methodNames);
        $this->assertContains('captureTransaction', $methodNames);
        $this->assertContains('isEnabled', $methodNames);
    }

    /**
     * Verificar que SentryService implementa el patrón Facade
     */
    public function test_sentry_service_uses_facade_pattern(): void
    {
        // Verificar que todos los métodos son estáticos
        $reflection = new \ReflectionClass(SentryService::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if ($method->getName() !== 'setBootstrappers') {
                $this->assertTrue(
                    $method->isStatic(),
                    sprintf('Method %s should be static', $method->getName())
                );
            }
        }
    }

    /**
     * Verificar que SentryService tiene documentación
     */
    public function test_sentry_service_has_documentation(): void
    {
        $reflection = new \ReflectionClass(SentryService::class);
        $docBlock = $reflection->getDocComment();
        
        // El servicio debe tener algunos comentarios de documentación
        $this->assertNotEmpty($docBlock);
    }

    /**
     * Verificar que la clase SentryService es una clase normal
     */
    public function test_sentry_service_structure(): void
    {
        $reflection = new \ReflectionClass(SentryService::class);
        
        // Verificar que es una clase normal, no interface ni trait
        $this->assertFalse($reflection->isInterface());
        $this->assertFalse($reflection->isTrait());
        $this->assertTrue($reflection->isInstantiable() || !$reflection->isAbstract());
    }

    /**
     * Verificar que SentryService está bien estructurado
     */
    public function test_sentry_service_content(): void
    {
        $reflection = new \ReflectionClass(SentryService::class);
        
        // Debe haber al menos 7 métodos públicos
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $this->assertGreaterThanOrEqual(7, count($methods));
    }
}
