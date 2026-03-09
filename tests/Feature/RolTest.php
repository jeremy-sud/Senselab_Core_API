<?php

namespace Tests\Feature;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RolTest extends TestCase
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
    public function puede_listar_roles(): void
    {
        $response = $this->authenticatedJson('GET', '/api/roles', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_rol(): void
    {
        $response = $this->authenticatedJson('POST', '/api/roles', [
            'nombre' => 'Supervisor',
            'descripcion' => 'Rol de supervisor',
        ], $this->usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('roles', ['nombre' => 'Supervisor']);
    }

    #[Test]
    public function puede_ver_rol(): void
    {
        $rol = Rol::create([
            'nombre' => 'Auxiliar',
            'descripcion' => 'Rol auxiliar',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', "/api/roles/{$rol->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_rol(): void
    {
        $rol = Rol::create([
            'nombre' => 'Temporal',
            'descripcion' => 'Rol temporal',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('PUT', "/api/roles/{$rol->id}", [
            'nombre' => 'Temporal Actualizado',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_rol_sin_usuarios(): void
    {
        $rol = Rol::create([
            'nombre' => 'Para Eliminar',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/roles/{$rol->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_nombre_requerido(): void
    {
        $response = $this->authenticatedJson('POST', '/api/roles', [
            'descripcion' => 'Sin nombre',
        ], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    #[Test]
    public function validacion_nombre_unico(): void
    {
        Rol::create([
            'nombre' => 'Duplicado',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('POST', '/api/roles', [
            'nombre' => 'Duplicado',
        ], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/roles');

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_crear_rol_con_permisos(): void
    {
        $permiso = Permiso::first();

        $response = $this->authenticatedJson('POST', '/api/roles', [
            'nombre' => 'Con Permisos',
            'permisos' => [$permiso->id],
        ], $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_filtrar_roles_activos(): void
    {
        $response = $this->authenticatedJson('GET', '/api/roles?activo=1', [], $this->usuario);

        $response->assertOk();
    }
}
