<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Webhook;
use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;
    protected Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;
    }

    public function test_usuario_puede_listar_webhooks()
    {
        Webhook::factory()->count(2)->for($this->empresa)->create();
        $response = $this->authenticatedJson('GET', '/api/webhooks', [], $this->usuario);
        $response->assertOk()->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nombre', 'url', 'eventos', 'descripcion', 'activo', 'creado_en', 'actualizado_en']
            ]
        ]);
    }

    public function test_usuario_puede_crear_webhook()
    {
        $payload = [
            'nombre' => 'Webhook de prueba',
            'url' => 'https://webhook.site/test',
            'eventos' => ['venta.creada'],
            'descripcion' => 'Prueba',
            'timeout_segundos' => 10,
            'max_reintentos' => 2,
            'activo' => true,
        ];
        $response = $this->authenticatedJson('POST', '/api/webhooks', $payload, $this->usuario);
        $response->assertCreated()->assertJsonPath('data.nombre', 'Webhook de prueba');
        $this->assertDatabaseHas('webhooks', [
            'nombre' => 'Webhook de prueba',
            'empresa_id' => $this->empresa->id,
        ]);
    }

    public function test_validacion_url_invalida()
    {
        $payload = [
            'nombre' => 'Webhook',
            'url' => 'ftp://no-valido',
            'eventos' => ['venta.creada'],
            'timeout_segundos' => 10,
            'max_reintentos' => 2,
            'activo' => true,
        ];
        $response = $this->authenticatedJson('POST', '/api/webhooks', $payload, $this->usuario);
        $response->assertUnprocessable()->assertJsonValidationErrors(['url']);
    }

    public function test_usuario_puede_ver_webhook()
    {
        $webhook = Webhook::factory()->for($this->empresa)->create();
        $response = $this->authenticatedJson('GET', "/api/webhooks/{$webhook->id}", [], $this->usuario);
        $response->assertOk()->assertJsonPath('data.id', $webhook->id);
    }

    public function test_usuario_puede_actualizar_webhook()
    {
        $webhook = Webhook::factory()->for($this->empresa)->create();
        $payload = ['nombre' => 'Actualizado'];
        $response = $this->authenticatedJson('PUT', "/api/webhooks/{$webhook->id}", $payload, $this->usuario);
        $response->assertOk()->assertJsonPath('data.nombre', 'Actualizado');
        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'nombre' => 'Actualizado',
        ]);
    }

    public function test_usuario_puede_eliminar_webhook()
    {
        $webhook = Webhook::factory()->for($this->empresa)->create();
        $response = $this->authenticatedJson('DELETE', "/api/webhooks/{$webhook->id}", [], $this->usuario);
        $response->assertOk();
    }

    public function test_usuario_no_puede_ver_webhook_de_otra_empresa()
    {
        $otraEmpresa = $this->createEmpresa(['email' => 'otra@empresa.com']);
        $webhook = Webhook::factory()->for($otraEmpresa)->create();
        $response = $this->authenticatedJson('GET', "/api/webhooks/{$webhook->id}", [], $this->usuario);
        $response->assertNotFound();
    }

    public function test_validacion_evento_invalido()
    {
        $payload = [
            'nombre' => 'Webhook',
            'url' => 'https://webhook.site/test',
            'eventos' => ['evento.invalido'],
            'timeout_segundos' => 10,
            'max_reintentos' => 2,
            'activo' => true,
        ];
        $response = $this->authenticatedJson('POST', '/api/webhooks', $payload, $this->usuario);
        $response->assertUnprocessable()->assertJsonValidationErrors(['eventos.0']);
    }

    public function test_eventos_disponibles()
    {
        $response = $this->authenticatedJson('GET', '/api/webhooks/eventos-disponibles', [], $this->usuario);
        $response->assertOk();
    }
}
