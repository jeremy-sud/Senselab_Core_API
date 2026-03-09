<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\ModeloBus;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModeloBusTest extends TestCase
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

    #[Test]
    public function puede_listar_modelos_buses(): void
    {
        ModeloBus::create(['nombre' => 'Modelo A']);

        $response = $this->authenticatedJson('GET', '/api/modelos-buses', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_modelo_bus(): void
    {
        $response = $this->authenticatedJson('POST', '/api/modelos-buses', [
            'nombre' => 'Marco Polo Torino',
        ], $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_modelo_bus(): void
    {
        $modelo = ModeloBus::create(['nombre' => 'Modelo Ver']);

        $response = $this->authenticatedJson('GET', "/api/modelos-buses/{$modelo->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_modelo_bus(): void
    {
        $modelo = ModeloBus::create(['nombre' => 'Modelo Viejo']);

        $response = $this->authenticatedJson('PUT', "/api/modelos-buses/{$modelo->id}", [
            'nombre' => 'Modelo Nuevo',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_modelo_bus(): void
    {
        $modelo = ModeloBus::create(['nombre' => 'Modelo Eliminar']);

        $response = $this->authenticatedJson('DELETE', "/api/modelos-buses/{$modelo->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_nombre_requerido(): void
    {
        $response = $this->authenticatedJson('POST', '/api/modelos-buses', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    #[Test]
    public function validacion_nombre_unico(): void
    {
        ModeloBus::create(['nombre' => 'Duplicado']);

        $response = $this->authenticatedJson('POST', '/api/modelos-buses', [
            'nombre' => 'Duplicado',
        ], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/modelos-buses');

        $response->assertUnauthorized();
    }
}
