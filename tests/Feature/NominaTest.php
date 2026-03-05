<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Empleado;
use App\Models\Cargo;
use App\Models\Departamento;
use App\Models\PeriodoNomina;
use App\Models\PagoNomina;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class NominaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
    }

    /**
     * Crea un Cargo de prueba (tabla global, sin empresa_id)
     */
    protected function createCargo(array $attrs = []): Cargo
    {
        return Cargo::firstOrCreate(
            ['nombre' => $attrs['nombre'] ?? 'Desarrollador'],
            array_merge([
                'descripcion' => 'Desarrollador de software',
                'activo' => true,
                'eliminado' => false,
            ], $attrs)
        );
    }

    /**
     * Crea un Departamento de prueba (tabla global, sin empresa_id)
     */
    protected function createDepartamento(array $attrs = []): Departamento
    {
        return Departamento::firstOrCreate(
            ['nombre' => $attrs['nombre'] ?? 'Tecnología'],
            array_merge([
                'descripcion' => 'Departamento de Tecnología',
                'activo' => true,
                'eliminado' => false,
            ], $attrs)
        );
    }

    /**
     * Crea un Empleado de prueba vinculado a una empresa
     */
    protected function createEmpleado(int $empresaId, array $attrs = []): Empleado
    {
        $cargo = $this->createCargo();
        $departamento = $this->createDepartamento();

        return Empleado::create(array_merge([
            'empresa_id' => $empresaId,
            'nombre' => 'Juan',
            'primer_apellido' => 'Pérez',
            'segundo_apellido' => 'López',
            'tipo_documento' => 'Cedula_Nacional',
            'numero_documento' => rand(100000000, 999999999),
            'fecha_nacimiento' => '1990-05-15',
            'fecha_ingreso' => '2024-01-01',
            'cargo_id' => $cargo->id,
            'departamento_id' => $departamento->id,
            'salario' => 850000.00,
            'email' => 'empleado' . rand(1, 9999) . '@test.com',
            'telefono' => '8888-' . rand(1000, 9999),
            'activo' => true,
            'eliminado' => false,
        ], $attrs));
    }

    // ========================================================================
    // EMPLEADOS
    // ========================================================================

    #[Test]
    public function test_puede_listar_empleados()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $this->createEmpleado($empresa->id);

        $response = $this->authenticatedJson('GET', '/api/empleados', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_empleado()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $cargo = $this->createCargo();

        $data = [
            'nombre' => 'María',
            'primer_apellido' => 'García',
            'segundo_apellido' => 'Solano',
            'tipo_documento' => 'Cedula_Nacional',
            'numero_documento' => '1-1234-5678',
            'fecha_nacimiento' => '1985-03-20',
            'fecha_ingreso' => '2025-01-15',
            'cargo_id' => $cargo->id,
            'salario' => 950000.00,
            'email' => 'maria@test.com',
            'telefono' => '8888-0001',
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/empleados', $data, $usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('empleados', [
            'nombre' => 'María',
            'primer_apellido' => 'García',
            'numero_documento' => '1-1234-5678',
            'empresa_id' => $empresa->id,
        ]);
    }

    #[Test]
    public function test_puede_ver_empleado()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $empleado = $this->createEmpleado($empresa->id);

        $response = $this->authenticatedJson('GET', "/api/empleados/{$empleado->id}", [], $usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_puede_actualizar_empleado()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $empleado = $this->createEmpleado($empresa->id);

        $response = $this->authenticatedJson('PUT', "/api/empleados/{$empleado->id}", [
            'nombre' => 'Juan Carlos',
            'primer_apellido' => $empleado->primer_apellido,
            'tipo_documento' => $empleado->tipo_documento,
            'numero_documento' => $empleado->numero_documento,
            'salario' => 1200000.00,
        ], $usuario);

        $response->assertStatus(200);
        $this->assertDatabaseHas('empleados', [
            'id' => $empleado->id,
            'nombre' => 'Juan Carlos',
        ]);
    }

    #[Test]
    public function test_puede_eliminar_empleado()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $empleado = $this->createEmpleado($empresa->id);

        $response = $this->authenticatedJson('DELETE', "/api/empleados/{$empleado->id}", [], $usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_validacion_empleado_sin_nombre()
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('POST', '/api/empleados', [
            'primer_apellido' => 'García',
            'tipo_documento' => 'Cedula_Nacional',
            'numero_documento' => '9-9999-9999',
            'salario' => 500000,
            // nombre omitido
        ], $usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_no_permite_empleado_sin_autenticacion()
    {
        $response = $this->postJson('/api/empleados', [
            'nombre' => 'Sin Auth',
        ]);

        $response->assertStatus(401);
    }

    // ========================================================================
    // PERÍODOS DE NÓMINA
    // ========================================================================

    #[Test]
    public function test_puede_listar_periodos_nomina()
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/periodos-nomina', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_periodo_nomina()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $data = [
            'nombre_periodo' => 'Enero 2025 - Quincena 1',
            'fecha_inicio' => '2025-01-01',
            'fecha_fin' => '2025-01-15',
            'fecha_pago_estimada' => '2025-01-16',
            'estado' => 'Abierto',
            'observaciones' => 'Primer período de nómina',
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/periodos-nomina', $data, $usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('periodos_nomina', [
            'nombre_periodo' => 'Enero 2025 - Quincena 1',
            'empresa_id' => $empresa->id,
        ]);
    }

    #[Test]
    public function test_puede_ver_periodo_nomina()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $periodo = PeriodoNomina::create([
            'empresa_id' => $empresa->id,
            'nombre_periodo' => 'Periodo Show Test',
            'fecha_inicio' => '2025-02-01',
            'fecha_fin' => '2025-02-15',
            'estado' => 'Abierto',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', "/api/periodos-nomina/{$periodo->id}", [], $usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_validacion_periodo_fecha_fin_antes_de_inicio()
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('POST', '/api/periodos-nomina', [
            'nombre_periodo' => 'Periodo Inválido',
            'fecha_inicio' => '2025-01-15',
            'fecha_fin' => '2025-01-01', // Antes que fecha_inicio
            'estado' => 'Abierto',
        ], $usuario);

        $response->assertStatus(422);
    }

    // ========================================================================
    // PAGOS DE NÓMINA
    // ========================================================================

    #[Test]
    public function test_puede_listar_pagos_nomina()
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/pagos-nomina', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_pago_nomina()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $empleado = $this->createEmpleado($empresa->id);

        $periodo = PeriodoNomina::create([
            'empresa_id' => $empresa->id,
            'nombre_periodo' => 'Periodo Pago Test',
            'fecha_inicio' => '2025-03-01',
            'fecha_fin' => '2025-03-15',
            'estado' => 'Abierto',
            'activo' => true,
            'eliminado' => false,
        ]);

        $formaPago = $this->getFormaPago();

        $data = [
            'empresa_id' => $empresa->id,
            'empleado_id' => $empleado->id,
            'periodo_nomina_id' => $periodo->id,
            'fecha_pago' => '2025-03-16',
            'monto_bruto' => 850000.00,
            'total_deducciones' => 127500.00,
            'monto_neto_pagado' => 722500.00,
            'metodo_pago_id' => $formaPago->id,
            'referencia_pago' => 'TRF-2025-001',
            'estado' => 'pendiente',
            'observaciones' => 'Pago quincena marzo',
        ];

        $response = $this->authenticatedJson('POST', '/api/pagos-nomina', $data, $usuario);

        $response->assertStatus(201);
    }
}
