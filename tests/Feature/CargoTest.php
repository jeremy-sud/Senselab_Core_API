<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CargoTest extends TestCase
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

    private function crearCargo(array $overrides = []): Cargo
    {
        return Cargo::create(array_merge([
            'nombre' => 'Cargo Test ' . rand(100, 999),
            'descripcion' => 'Descripción del cargo',
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_cargos(): void
    {
        $this->crearCargo(['nombre' => 'Gerente']);
        $this->crearCargo(['nombre' => 'Vendedor']);

        $response = $this->authenticatedJson('GET', '/api/cargos', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_cargo_con_datos_validos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/cargos', [
            'nombre' => 'Director Financiero',
            'descripcion' => 'Responsable del área financiera',
        ], $this->usuario);

        $response->assertStatus(201);

        $this->assertDatabaseHas('cargos', [
            'nombre' => 'Director Financiero',
        ]);
    }

    #[Test]
    public function puede_ver_cargo(): void
    {
        $cargo = $this->crearCargo();

        $response = $this->authenticatedJson('GET', "/api/cargos/{$cargo->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_cargo(): void
    {
        $cargo = $this->crearCargo();

        $response = $this->authenticatedJson('PUT', "/api/cargos/{$cargo->id}", [
            'nombre' => 'Cargo Actualizado',
        ], $this->usuario);

        $response->assertOk();

        $this->assertDatabaseHas('cargos', [
            'id' => $cargo->id,
            'nombre' => 'Cargo Actualizado',
        ]);
    }

    #[Test]
    public function puede_eliminar_cargo_sin_empleados(): void
    {
        $cargo = $this->crearCargo();

        $response = $this->authenticatedJson('DELETE', "/api/cargos/{$cargo->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function no_puede_eliminar_cargo_con_empleados_asignados(): void
    {
        $cargo = $this->crearCargo(['nombre' => 'Cargo con empleados']);

        Empleado::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Empleado Asignado',
            'primer_apellido' => 'Test',
            'tipo_documento' => 'Cedula_Nacional',
            'numero_documento' => '123456789',
            'cargo_id' => $cargo->id,
            'salario' => 500000,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/cargos/{$cargo->id}", [], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function validacion_nombre_requerido(): void
    {
        $response = $this->authenticatedJson('POST', '/api/cargos', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    #[Test]
    public function validacion_nombre_unico(): void
    {
        $this->crearCargo(['nombre' => 'Gerente General']);

        $response = $this->authenticatedJson('POST', '/api/cargos', [
            'nombre' => 'Gerente General',
        ], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/cargos');

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_filtrar_cargos_activos(): void
    {
        $this->crearCargo(['nombre' => 'Activo', 'activo' => true]);
        $this->crearCargo(['nombre' => 'Inactivo', 'activo' => false]);

        $response = $this->authenticatedJson('GET', '/api/cargos?activo=1', [], $this->usuario);

        $response->assertOk();
    }
}
