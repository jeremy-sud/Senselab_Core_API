<?php

namespace Tests\Feature;

use App\Models\TipoCuenta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TipoCuentaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
    }

    private function crearTipoCuenta(array $overrides = []): TipoCuenta
    {
        return TipoCuenta::create(array_merge([
            'nombre' => 'Activo Corriente',
            'descripcion' => 'Cuentas de activo corriente',
            'naturaleza' => 'Deudora',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_tipos_cuenta(): void
    {
        $this->crearTipoCuenta();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/tipos-cuentas', [], $usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_tipo_cuenta(): void
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('POST', '/api/tipos-cuentas', [
            'nombre' => 'Pasivo Corriente',
            'naturaleza' => 'Acreedora',
        ], $usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_tipo_cuenta(): void
    {
        $tipo = $this->crearTipoCuenta();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', "/api/tipos-cuentas/{$tipo->id}", [], $usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_tipo_cuenta(): void
    {
        $tipo = $this->crearTipoCuenta();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('PUT', "/api/tipos-cuentas/{$tipo->id}", [
            'nombre' => 'Activo No Corriente',
        ], $usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_tipo_cuenta(): void
    {
        $tipo = $this->crearTipoCuenta();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('DELETE', "/api/tipos-cuentas/{$tipo->id}", [], $usuario);

        $response->assertOk();
    }

    #[Test]

    public function puede_listar_activos(): void
    {
        $this->crearTipoCuenta();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/tipos-cuentas/activos/list', [], $usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_nombre_al_crear(): void
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('POST', '/api/tipos-cuentas', [
            'naturaleza' => 'Deudora',
        ], $usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/tipos-cuentas');

        $response->assertUnauthorized();
    }
}
