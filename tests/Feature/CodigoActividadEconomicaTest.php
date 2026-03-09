<?php

namespace Tests\Feature;

use App\Models\CodigoActividadEconomica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CodigoActividadEconomicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
    }

    private function crearCodigo(array $overrides = []): CodigoActividadEconomica
    {
        return CodigoActividadEconomica::create(array_merge([
            'codigo' => '620100',
            'descripcion' => 'Actividades de programación informática',
            'categoria_principal' => 'Tecnología',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_codigos(): void
    {
        $this->crearCodigo();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/codigos-actividad-economica', [], $usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_codigo(): void
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('POST', '/api/codigos-actividad-economica', [
            'codigo' => '620200',
            'descripcion' => 'Consultoría informática',
        ], $usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_codigo(): void
    {
        $codigo = $this->crearCodigo();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', "/api/codigos-actividad-economica/{$codigo->id}", [], $usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_codigo(): void
    {
        $codigo = $this->crearCodigo();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('PUT', "/api/codigos-actividad-economica/{$codigo->id}", [
            'descripcion' => 'Descripción actualizada',
        ], $usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_codigo(): void
    {
        $codigo = $this->crearCodigo();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('DELETE', "/api/codigos-actividad-economica/{$codigo->id}", [], $usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_codigo_al_crear(): void
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('POST', '/api/codigos-actividad-economica', [
            'descripcion' => 'Sin codigo',
        ], $usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/codigos-actividad-economica');

        $response->assertUnauthorized();
    }
}
