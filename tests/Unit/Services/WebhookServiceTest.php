<?php

namespace Tests\Unit\Services;

use App\Models\Empresa;
use App\Models\Webhook;
use App\Services\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;
    protected WebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = $this->createEmpresa();
        $this->service = app(WebhookService::class);
    }

    public function test_crear_webhook_generates_secret()
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Webhook',
            'url' => 'https://webhook.site/test',
            'eventos' => ['venta.creada'],
            'timeout_segundos' => 10,
            'max_reintentos' => 2,
            'activo' => true,
        ];
        $webhook = $this->service->crear($data);
        $this->assertNotEmpty($webhook->secret);
        $this->assertEquals(64, strlen($webhook->secret));
    }

    public function test_listar_filtra_por_empresa()
    {
        Webhook::factory()->count(2)->for($this->empresa)->create();
        $otraEmpresa = $this->createEmpresa(['email' => 'otra' . rand(1000, 9999) . '@empresa.com']);
        Webhook::factory()->for($otraEmpresa)->create();
        $result = $this->service->listar(['empresa_id' => $this->empresa->id]);
        $this->assertCount(2, $result);
    }

    public function test_regenerar_secret()
    {
        $webhook = Webhook::factory()->for($this->empresa)->create();
        $oldSecret = $webhook->secret;
        $nuevo = $this->service->regenerarSecret($webhook);
        $this->assertNotEquals($oldSecret, $nuevo);
        $this->assertEquals(64, strlen($nuevo));
    }

    // ...más tests: obtenerLogs, probar, filtros, etc.
}
