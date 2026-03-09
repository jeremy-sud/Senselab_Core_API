<?php

namespace Tests\Feature;

use App\Models\CuentaContable;
use App\Models\TipoCuenta;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CuentaContableTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected TipoCuenta $tipoCuenta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();

        $this->tipoCuenta = TipoCuenta::create([
            'nombre' => 'Activo',
            'naturaleza' => 'Deudora',
            'activo' => true,
        ]);
    }

    private function crearCuenta(array $overrides = []): CuentaContable
    {
        return CuentaContable::create(array_merge([
            'empresa_id' => $this->usuario->empresa_id,
            'nombre' => 'Caja General',
            'codigo' => '1.1.01',
            'tipo_cuenta_id' => $this->tipoCuenta->id,
            'permite_movimientos' => true,
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_cuentas(): void
    {
        $this->crearCuenta();

        $response = $this->authenticatedJson('GET', '/api/cuentas-contables', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_cuenta(): void
    {
        $response = $this->authenticatedJson('POST', '/api/cuentas-contables', [
            'nombre' => 'Bancos',
            'codigo' => '1.1.02',
            'tipo_cuenta_id' => $this->tipoCuenta->id,
            'permite_movimientos' => true,
        ], $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_cuenta(): void
    {
        $cuenta = $this->crearCuenta();

        $response = $this->authenticatedJson('GET', "/api/cuentas-contables/{$cuenta->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_cuenta(): void
    {
        $cuenta = $this->crearCuenta();

        $response = $this->authenticatedJson('PUT', "/api/cuentas-contables/{$cuenta->id}", [
            'nombre' => 'Caja General Actualizada',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_cuenta(): void
    {
        $cuenta = $this->crearCuenta();

        $response = $this->authenticatedJson('DELETE', "/api/cuentas-contables/{$cuenta->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_obtener_arbol(): void
    {
        $this->crearCuenta();

        $response = $this->authenticatedJson('GET', '/api/cuentas-contables/arbol/completo', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_listar_para_movimientos(): void
    {
        $this->crearCuenta(['permite_movimientos' => true]);

        $response = $this->authenticatedJson('GET', '/api/cuentas-contables/movimientos/list', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_nombre_al_crear(): void
    {
        $response = $this->authenticatedJson('POST', '/api/cuentas-contables', [
            'codigo' => '1.1.99',
            'tipo_cuenta_id' => $this->tipoCuenta->id,
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/cuentas-contables');

        $response->assertUnauthorized();
    }
}
