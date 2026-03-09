<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PermisoTest extends TestCase
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

    #[Test]
    public function puede_listar_permisos(): void
    {
        $response = $this->authenticatedJson('GET', '/api/permisos', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_permiso(): void
    {
        $response = $this->authenticatedJson('POST', '/api/permisos', [
            'nombre' => 'Permiso Test',
            'slug' => 'test-permiso-unico',
            'modulo' => 'Test',
        ], $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_permiso(): void
    {
        $permiso = Permiso::first();

        $response = $this->authenticatedJson('GET', "/api/permisos/{$permiso->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_permiso(): void
    {
        $permiso = Permiso::create([
            'nombre' => 'Para Actualizar',
            'slug' => 'para-actualizar',
            'modulo' => 'Test',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('PUT', "/api/permisos/{$permiso->id}", [
            'nombre' => 'Actualizado',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_permiso_sin_roles(): void
    {
        $permiso = Permiso::create([
            'nombre' => 'Para Eliminar',
            'slug' => 'para-eliminar-test',
            'modulo' => 'Test',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/permisos/{$permiso->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_nombre_requerido(): void
    {
        $response = $this->authenticatedJson('POST', '/api/permisos', [
            'codigo_unico' => 'test-sin-nombre',
        ], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    #[Test]
    public function validacion_codigo_unico_requerido(): void
    {
        $response = $this->authenticatedJson('POST', '/api/permisos', [
            'nombre' => 'Sin Codigo',
        ], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/permisos');

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_filtrar_por_modulo(): void
    {
        $response = $this->authenticatedJson('GET', '/api/permisos?modulo=Ventas', [], $this->usuario);

        $response->assertOk();
    }
}
