<?php

namespace Tests\Feature;

use App\Models\Etiqueta;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EtiquetaTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
    }

    private function crearEtiqueta(array $overrides = []): Etiqueta
    {
        return Etiqueta::create(array_merge([
            'empresa_id' => $this->usuario->empresa_id,
            'nombre' => 'Importante',
            'color_hex' => '#FF0000',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_etiquetas(): void
    {
        $this->crearEtiqueta();

        $response = $this->authenticatedJson('GET', '/api/etiquetas', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_etiqueta(): void
    {
        $response = $this->authenticatedJson('POST', '/api/etiquetas', [
            'nombre' => 'Urgente',
            'color_hex' => '#00FF00',
        ], $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_etiqueta(): void
    {
        $etiqueta = $this->crearEtiqueta();

        $response = $this->authenticatedJson('GET', "/api/etiquetas/{$etiqueta->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_etiqueta(): void
    {
        $etiqueta = $this->crearEtiqueta();

        $response = $this->authenticatedJson('PUT', "/api/etiquetas/{$etiqueta->id}", [
            'nombre' => 'Etiqueta Actualizada',
            'color_hex' => '#0000FF',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_etiqueta(): void
    {
        $etiqueta = $this->crearEtiqueta();

        $response = $this->authenticatedJson('DELETE', "/api/etiquetas/{$etiqueta->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_nombre_al_crear(): void
    {
        $response = $this->authenticatedJson('POST', '/api/etiquetas', [
            'color_hex' => '#FF00FF',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/etiquetas');

        $response->assertUnauthorized();
    }
}
