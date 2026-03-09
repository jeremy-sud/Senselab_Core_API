<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClienteTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;
    }

    private function datosClienteValido(array $overrides = []): array
    {
        return array_merge([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '1' . rand(10000000, 99999999),
            'nombre' => 'Juan',
            'apellidos' => 'Pérez Castro',
            'email' => 'juan@example.com',
            'telefono' => '+506 2222-3333',
            'direccion' => 'San José, Costa Rica',
            'activo' => true,
        ], $overrides);
    }

    private function crearCliente(array $overrides = []): Cliente
    {
        return Cliente::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '1' . rand(10000000, 99999999),
            'nombre' => 'Cliente Test',
            'apellidos' => 'Apellido Test',
            'email' => 'cliente' . rand(100, 999) . '@test.com',
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_clientes(): void
    {
        $this->crearCliente(['nombre' => 'Cliente 1']);
        $this->crearCliente(['nombre' => 'Cliente 2']);

        $response = $this->authenticatedJson('GET', '/api/clientes', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_cliente_con_datos_validos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/clientes', $this->datosClienteValido(), $this->usuario);

        $response->assertStatus(201);

        $this->assertDatabaseHas('clientes', [
            'nombre' => 'Juan',
        ]);
    }

    #[Test]
    public function puede_ver_cliente(): void
    {
        $cliente = $this->crearCliente();

        $response = $this->authenticatedJson('GET', "/api/clientes/{$cliente->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_cliente(): void
    {
        $cliente = $this->crearCliente();

        $response = $this->authenticatedJson('PUT', "/api/clientes/{$cliente->id}", [
            'nombre' => 'Nombre Actualizado',
        ], $this->usuario);

        $response->assertOk();

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre' => 'Nombre Actualizado',
        ]);
    }

    #[Test]
    public function puede_eliminar_cliente(): void
    {
        $cliente = $this->crearCliente();

        $response = $this->authenticatedJson('DELETE', "/api/clientes/{$cliente->id}", [], $this->usuario);

        $response->assertOk();

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'eliminado' => true,
        ]);
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/clientes', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['empresa_id', 'tipo_identificacion', 'numero_identificacion', 'nombre']);
    }

    #[Test]
    public function validacion_tipo_identificacion_invalido(): void
    {
        $datos = $this->datosClienteValido(['tipo_identificacion' => '99']);

        $response = $this->authenticatedJson('POST', '/api/clientes', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_identificacion']);
    }

    #[Test]
    public function validacion_email_formato_invalido(): void
    {
        $datos = $this->datosClienteValido(['email' => 'no-es-email']);

        $response = $this->authenticatedJson('POST', '/api/clientes', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function no_puede_crear_cliente_con_identificacion_duplicada_misma_empresa(): void
    {
        $this->crearCliente([
            'tipo_identificacion' => '01',
            'numero_identificacion' => '123456789',
        ]);

        $datos = $this->datosClienteValido(['numero_identificacion' => '123456789']);

        $response = $this->authenticatedJson('POST', '/api/clientes', $datos, $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion_para_listar(): void
    {
        $response = $this->getJson('/api/clientes');

        $response->assertUnauthorized();
    }

    #[Test]
    public function requiere_autenticacion_para_crear(): void
    {
        $response = $this->postJson('/api/clientes', []);

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_buscar_clientes_por_nombre(): void
    {
        $this->crearCliente(['nombre' => 'María García']);
        $this->crearCliente(['nombre' => 'Pedro López']);

        $response = $this->authenticatedJson('GET', '/api/clientes?search=María', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_filtrar_clientes_activos(): void
    {
        $this->crearCliente(['nombre' => 'Activo', 'activo' => true]);
        $this->crearCliente(['nombre' => 'Inactivo', 'activo' => false]);

        $response = $this->authenticatedJson('GET', '/api/clientes?activos=1', [], $this->usuario);

        $response->assertOk();
    }
}
