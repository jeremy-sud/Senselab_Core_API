<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SucursalTest extends TestCase
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

    private function crearSucursal(array $overrides = []): Sucursal
    {
        return Sucursal::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Sucursal ' . uniqid(),
            'direccion' => 'Dirección de prueba',
            'telefono' => '22223333',
            'email' => 'sucursal' . uniqid() . '@test.com',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_sucursales(): void
    {
        $this->crearSucursal(['nombre' => 'Sucursal A']);
        $this->crearSucursal(['nombre' => 'Sucursal B']);

        $response = $this->authenticatedJson('GET', '/api/sucursales', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_sucursal(): void
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Sucursal Central',
            'direccion' => 'San José, Costa Rica',
            'telefono' => '22001100',
            'email' => 'central@empresa.com',
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/sucursales', $data, $this->usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('sucursales', ['nombre' => 'Sucursal Central']);
    }

    #[Test]
    public function puede_ver_sucursal(): void
    {
        $sucursal = $this->crearSucursal();

        $response = $this->authenticatedJson('GET', "/api/sucursales/{$sucursal->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_sucursal(): void
    {
        $sucursal = $this->crearSucursal(['nombre' => 'Original']);

        $response = $this->authenticatedJson('PUT', "/api/sucursales/{$sucursal->id}", [
            'nombre' => 'Sucursal Actualizada',
            'direccion' => 'Nueva dirección',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_sucursal(): void
    {
        $sucursal = $this->crearSucursal();

        $response = $this->authenticatedJson('DELETE', "/api/sucursales/{$sucursal->id}", [], $this->usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function requiere_nombre_al_crear(): void
    {
        $response = $this->authenticatedJson('POST', '/api/sucursales', [
            'empresa_id' => $this->empresa->id,
            'direccion' => 'Sin nombre',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_empresa_id_al_crear(): void
    {
        $response = $this->authenticatedJson('POST', '/api/sucursales', [
            'nombre' => 'Sucursal sin empresa',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/sucursales');

        $response->assertUnauthorized();
    }
}
