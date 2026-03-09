<?php

namespace Tests\Feature;

use App\Models\Cargo;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\PagoNomina;
use App\Models\PeriodoNomina;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PagoNominaTest extends TestCase
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

        $cargo = Cargo::create([
            'nombre' => 'Desarrollador',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->empleado = Empleado::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Empleado Test',
            'primer_apellido' => 'Apellido',
            'tipo_documento' => 'Cedula_Nacional',
            'numero_documento' => '123456789',
            'cargo_id' => $cargo->id,
            'salario' => 800000.00,
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->periodo = PeriodoNomina::create([
            'empresa_id' => $this->empresa->id,
            'nombre_periodo' => 'Enero 2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-15',
            'estado' => 'Abierto',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    private function datosPagoValido(array $overrides = []): array
    {
        return array_merge([
            'empleado_id' => $this->empleado->id,
            'periodo_nomina_id' => $this->periodo->id,
            'fecha_pago' => '2026-01-16',
            'monto_bruto' => 800000.00,
            'total_deducciones' => 120000.00,
            'monto_neto_pagado' => 680000.00,
            'estado' => 'pendiente',
            'observaciones' => 'Pago quincenal enero',
        ], $overrides);
    }

    private function crearPago(array $overrides = []): PagoNomina
    {
        return PagoNomina::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'empleado_id' => $this->empleado->id,
            'periodo_nomina_id' => $this->periodo->id,
            'fecha_pago' => '2026-01-16',
            'monto_bruto' => 800000.00,
            'total_deducciones' => 120000.00,
            'monto_neto_pagado' => 680000.00,
            'estado' => 'pendiente',
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_pagos_nomina(): void
    {
        $this->crearPago();

        $response = $this->authenticatedJson('GET', '/api/pagos-nomina', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_pago_nomina_con_datos_validos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/pagos-nomina', $this->datosPagoValido(), $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_pago_nomina(): void
    {
        $pago = $this->crearPago();

        $response = $this->authenticatedJson('GET', "/api/pagos-nomina/{$pago->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_pago_pendiente(): void
    {
        $pago = $this->crearPago(['estado' => 'pendiente']);

        $response = $this->authenticatedJson('PUT', "/api/pagos-nomina/{$pago->id}", [
            'observaciones' => 'Observación actualizada',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_pago_pendiente(): void
    {
        $pago = $this->crearPago(['estado' => 'pendiente']);

        $response = $this->authenticatedJson('DELETE', "/api/pagos-nomina/{$pago->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/pagos-nomina', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['empleado_id', 'periodo_nomina_id', 'fecha_pago', 'monto_bruto']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/pagos-nomina');

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_listar_pagos_por_empleado(): void
    {
        $this->crearPago();

        $response = $this->authenticatedJson(
            'GET',
            "/api/pagos-nomina/empleado/{$this->empleado->id}",
            [],
            $this->usuario
        );

        $response->assertOk();
    }
}
