<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlmacenTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();

        $this->sucursal = Sucursal::create([
            'empresa_id' => $this->usuario->empresa_id,
            'nombre' => 'Sucursal Principal',
            'activo' => true,
        ]);
    }

    private function crearAlmacen(array $overrides = []): Almacen
    {
        return Almacen::create(array_merge([
            'empresa_id' => $this->usuario->empresa_id,
            'nombre' => 'Almacén Principal',
            'codigo' => 'ALM-001',
            'descripcion' => 'Almacén principal de la empresa',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_almacenes(): void
    {
        $this->crearAlmacen();

        $response = $this->authenticatedJson('GET', '/api/almacenes', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_almacen(): void
    {
        $response = $this->authenticatedJson('POST', '/api/almacenes', [
            'empresa_id' => $this->usuario->empresa_id,
            'sucursal_id' => $this->sucursal->id,
            'nombre' => 'Almacén Secundario',
            'codigo' => 'ALM-002',
        ], $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_almacen(): void
    {
        $almacen = $this->crearAlmacen();

        $response = $this->authenticatedJson('GET', "/api/almacenes/{$almacen->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_almacen(): void
    {
        $almacen = $this->crearAlmacen();

        $response = $this->authenticatedJson('PUT', "/api/almacenes/{$almacen->id}", [
            'nombre' => 'Almacén Actualizado',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_almacen(): void
    {
        $almacen = $this->crearAlmacen();

        $response = $this->authenticatedJson('DELETE', "/api/almacenes/{$almacen->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_nombre_al_crear(): void
    {
        $response = $this->authenticatedJson('POST', '/api/almacenes', [
            'codigo' => 'ALM-X',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/almacenes');

        $response->assertUnauthorized();
    }
}
