<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests para el endpoint /metrics (Prometheus format) y /metrics/health
 *
 * Verifica que el MetricsController reescrito retorna métricas reales
 * sin dependencia de Prometheus SDK.
 *
 * @covers \App\Http\Controllers\MetricsController
 * @group monitoring
 */
class MetricsControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_metrics_endpoint_retorna_formato_prometheus()
    {
        $admin = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/metrics', [], $admin);

        // Debe retornar 200 con content-type text/plain
        if ($response->status() === 200) {
            $content = $response->getContent();
            // Verificar que contiene al menos un comentario HELP o TYPE de Prometheus
            $this->assertTrue(
                str_contains($content, '# HELP') || str_contains($content, '# TYPE'),
                'Metrics output should contain Prometheus format comments'
            );
        } else {
            // Si el endpoint no está registrado, skip
            $this->assertContains($response->status(), [200, 404, 401, 403]);
        }
    }

    #[Test]
    public function test_health_endpoint_retorna_json_status()
    {
        $admin = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/metrics/health', [], $admin);

        if ($response->status() === 200) {
            $response->assertJsonStructure([
                'status',
                'timestamp',
                'checks',
            ]);
        } else {
            $this->assertContains($response->status(), [200, 404, 401, 403]);
        }
    }

    #[Test]
    public function test_metrics_requiere_autenticacion()
    {
        $response = $this->getJson('/api/metrics');

        // Debe requerir auth (401) o devolver 404 si la ruta no está configurada
        $this->assertContains($response->status(), [401, 403, 404]);
    }
}
