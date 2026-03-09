<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EmpleadoTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected Cargo $cargo;
    protected Departamento $departamento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;
        $this->cargo = Cargo::create([
            'nombre' => 'Desarrollador',
            'descripcion' => 'Desarrollo de software',
            'activo' => true,
            'eliminado' => false,
        ]);
        $this->departamento = Departamento::create([
            'nombre' => 'Tecnología',
            'descripcion' => 'Departamento de TI',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    private function datosEmpleadoValido(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Carlos',
            'primer_apellido' => 'González',
            'segundo_apellido' => 'Mora',
            'tipo_documento' => 'cedula',
            'numero_documento' => '1' . rand(10000000, 99999999),
            'fecha_nacimiento' => '1990-05-15',
            'fecha_ingreso' => '2024-01-01',
            'departamento_id' => $this->departamento->id,
            'cargo_id' => $this->cargo->id,
            'salario' => 850000.00,
            'email' => 'carlos' . rand(100, 999) . '@test.com',
            'telefono' => '+506 8888-9999',
            'direccion' => 'San José, Costa Rica',
            'activo' => true,
        ], $overrides);
    }

    private function crearEmpleado(array $overrides = []): Empleado
    {
        return Empleado::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Empleado Test',
            'primer_apellido' => 'Apellido',
            'tipo_documento' => 'cedula',
            'numero_documento' => '1' . rand(10000000, 99999999),
            'fecha_nacimiento' => '1990-01-01',
            'fecha_ingreso' => '2024-01-01',
            'departamento_id' => $this->departamento->id,
            'cargo_id' => $this->cargo->id,
            'salario' => 500000.00,
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_empleados(): void
    {
        $this->crearEmpleado(['nombre' => 'Empleado 1']);
        $this->crearEmpleado(['nombre' => 'Empleado 2']);

        $response = $this->authenticatedJson('GET', '/api/empleados', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_empleado_con_datos_validos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/empleados', $this->datosEmpleadoValido(), $this->usuario);

        $response->assertStatus(201);

        $this->assertDatabaseHas('empleados', [
            'nombre' => 'Carlos',
            'primer_apellido' => 'González',
        ]);
    }

    #[Test]
    public function puede_ver_empleado(): void
    {
        $empleado = $this->crearEmpleado();

        $response = $this->authenticatedJson('GET', "/api/empleados/{$empleado->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_empleado(): void
    {
        $empleado = $this->crearEmpleado();

        $response = $this->authenticatedJson('PUT', "/api/empleados/{$empleado->id}", [
            'nombre' => 'Nombre Actualizado',
            'salario' => 900000.00,
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_empleado(): void
    {
        $empleado = $this->crearEmpleado();

        $response = $this->authenticatedJson('DELETE', "/api/empleados/{$empleado->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/empleados', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'primer_apellido', 'tipo_documento', 'numero_documento', 'salario', 'departamento_id']);
    }

    #[Test]
    public function validacion_tipo_documento_invalido(): void
    {
        $datos = $this->datosEmpleadoValido(['tipo_documento' => 'invalido_tipo']);

        $response = $this->authenticatedJson('POST', '/api/empleados', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_documento']);
    }

    #[Test]
    public function validacion_salario_negativo(): void
    {
        $datos = $this->datosEmpleadoValido(['salario' => -1000]);

        $response = $this->authenticatedJson('POST', '/api/empleados', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['salario']);
    }

    #[Test]
    public function requiere_autenticacion_para_listar(): void
    {
        $response = $this->getJson('/api/empleados');

        $response->assertUnauthorized();
    }

    #[Test]
    public function requiere_autenticacion_para_crear(): void
    {
        $response = $this->postJson('/api/empleados', []);

        $response->assertUnauthorized();
    }

    #[Test]
    public function validacion_email_formato_invalido(): void
    {
        $datos = $this->datosEmpleadoValido(['email' => 'no-es-email']);

        $response = $this->authenticatedJson('POST', '/api/empleados', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
