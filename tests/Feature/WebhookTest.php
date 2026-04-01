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
        $this->seedPermisos();
        $this->seedRoles();
        $this->empresa = $this->createEmpresa();
        $this->usuario = $this->createUsuario($this->empresa, permisos: [
            'ver-webhooks', 'crear-webhooks', 'editar-webhooks', 'eliminar-webhooks',
        ]);
    }

    public function test_usuario_puede_listar_webhooks()
    {
        Webhook::factory()->count(2)->for($this->empresa)->create();
        $response = $this->authenticatedJson($this->usuario, 'GET', '/api/webhooks');
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
        $response = $this->authenticatedJson($this->usuario, 'POST', '/api/webhooks', $payload);
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
        $response = $this->authenticatedJson($this->usuario, 'POST', '/api/webhooks', $payload);
        $response->assertUnprocessable()->assertJsonValidationErrors(['url']);
    }

    public function test_usuario_puede_ver_webhook()
    {
        $webhook = Webhook::factory()->for($this->empresa)->create();
        $response = $this->authenticatedJson($this->usuario, 'GET', "/api/webhooks/{$webhook->id}");
        $response->assertOk()->assertJsonPath('data.id', $webhook->id);
    }

    public function test_usuario_puede_actualizar_webhook()
    {
        $webhook = Webhook::factory()->for($this->empresa)->create();
        $payload = ['nombre' => 'Actualizado'];
        $response = $this->authenticatedJson($this->usuario, 'PUT', "/api/webhooks/{$webhook->id}", $payload);
        $response->assertOk()->assertJsonPath('data.nombre', 'Actualizado');
        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'nombre' => 'Actualizado',
        ]);
    }

    public function test_usuario_puede_eliminar_webhook()
    {
        $webhook = Webhook::factory()->for($this->empresa)->create();
        $response = $this->authenticatedJson($this->usuario, 'DELETE', "/api/webhooks/{$webhook->id}");
        $response->assertOk();
        $this->assertSoftDeleted('webhooks', [
            'id' => $webhook->id,
        ]);
    }

    public function test_usuario_no_puede_ver_webhook_de_otra_empresa()
    {
        $otraEmpresa = $this->createEmpresa();
        $webhook = Webhook::factory()->for($otraEmpresa)->create();
        $response = $this->authenticatedJson($this->usuario, 'GET', "/api/webhooks/{$webhook->id}");
        $response->assertForbidden();
    }

    public function test_usuario_sin_permiso_no_puede_crear()
    {
        $usuarioSinPermiso = $this->createUsuario($this->empresa, permisos: ['ver-webhooks']);
        $payload = [
            'nombre' => 'Webhook',
            'url' => 'https://webhook.site/test',
            'eventos' => ['venta.creada'],
            'timeout_segundos' => 10,
            'max_reintentos' => 2,
            'activo' => true,
        ];
        $response = $this->authenticatedJson($usuarioSinPermiso, 'POST', '/api/webhooks', $payload);
        $response->assertForbidden();
    }

    // ...más tests: regenerar secret, logs, test, eventosDisponibles, validaciones, etc.
}
