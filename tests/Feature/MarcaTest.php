<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Marca;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarcaTest extends TestCase
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

    private function crearMarca(array $overrides = []): Marca
    {
        return Marca::create(array_merge([
            'nombre' => 'Marca ' . uniqid(),
            'descripcion' => 'Marca de prueba',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_marcas(): void
    {
        $this->crearMarca(['nombre' => 'Samsung']);
        $this->crearMarca(['nombre' => 'LG']);

        $response = $this->authenticatedJson('GET', '/api/marcas', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_marca(): void
    {
        $data = [
            'nombre' => 'Sony',
            'descripcion' => 'Marca electrónica',
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/marcas', $data, $this->usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('marcas', ['nombre' => 'Sony']);
    }

    #[Test]
    public function puede_ver_marca(): void
    {
        $marca = $this->crearMarca(['nombre' => 'Apple']);

        $response = $this->authenticatedJson('GET', "/api/marcas/{$marca->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_marca(): void
    {
        $marca = $this->crearMarca(['nombre' => 'Origina']);

        $response = $this->authenticatedJson('PUT', "/api/marcas/{$marca->id}", [
            'nombre' => 'Actualizada',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_marca(): void
    {
        $marca = $this->crearMarca();

        $response = $this->authenticatedJson('DELETE', "/api/marcas/{$marca->id}", [], $this->usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function requiere_nombre_al_crear(): void
    {
        $response = $this->authenticatedJson('POST', '/api/marcas', [
            'descripcion' => 'Sin nombre',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/marcas');

        $response->assertUnauthorized();
    }
}
