<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\NominaEmpleado;
use App\Models\PeriodoNomina;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NominaEmpleadoTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected Empleado $empleado;
    protected PeriodoNomina $periodo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;

        $this->empleado = Empleado::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'María',
            'primer_apellido' => 'González',
            'tipo_documento' => 'cedula_fisica',
            'numero_documento' => '987654321',
            'email' => 'maria@test.com',
            'fecha_ingreso' => now()->subYear()->format('Y-m-d'),
            'activo' => true,
        ]);

        $this->periodo = PeriodoNomina::create([
            'empresa_id' => $this->empresa->id,
            'nombre_periodo' => 'Enero 2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-15',
            'fecha_pago' => '2026-01-16',
            'estado' => 'Abierto',
            'activo' => true,
        ]);
    }

    private function crearNominaEmpleado(array $overrides = []): NominaEmpleado
    {
        return NominaEmpleado::create(array_merge([
            'periodo_nomina_id' => $this->periodo->id,
            'empleado_id' => $this->empleado->id,
            'salario_bruto' => 500000.00,
            'horas_extras' => 0,
            'monto_horas_extras' => 0,
            'bonificaciones' => 0,
            'total_devengado' => 500000.00,
            'deducciones_ccss' => 47250.00,
            'deducciones_impuesto_renta' => 0,
            'otras_deducciones' => 0,
            'total_deducciones' => 47250.00,
            'salario_neto' => 452750.00,
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_nominas_empleado(): void
    {
        $this->crearNominaEmpleado();

        $response = $this->authenticatedJson('GET', '/api/nomina-empleados', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_nomina_empleado(): void
    {
        $data = [
            'periodo_nomina_id' => $this->periodo->id,
            'empleado_id' => $this->empleado->id,
            'salario_bruto' => 600000.00,
            'horas_extras' => 5,
            'monto_horas_extras' => 15000.00,
            'bonificaciones' => 10000.00,
            'deducciones_ccss' => 56700.00,
            'deducciones_impuesto_renta' => 0,
            'otras_deducciones' => 0,
        ];

        $response = $this->authenticatedJson('POST', '/api/nomina-empleados', $data, $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_nomina_empleado(): void
    {
        $nomina = $this->crearNominaEmpleado();

        $response = $this->authenticatedJson('GET', "/api/nomina-empleados/{$nomina->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_nomina_empleado(): void
    {
        $nomina = $this->crearNominaEmpleado();

        $response = $this->authenticatedJson('PUT', "/api/nomina-empleados/{$nomina->id}", [
            'bonificaciones' => 25000.00,
            'observaciones' => 'Bono por desempeño',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_nomina_empleado(): void
    {
        $nomina = $this->crearNominaEmpleado();

        $response = $this->authenticatedJson('DELETE', "/api/nomina-empleados/{$nomina->id}", [], $this->usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/nomina-empleados');

        $response->assertUnauthorized();
    }
}
