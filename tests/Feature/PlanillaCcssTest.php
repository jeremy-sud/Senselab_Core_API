<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\PeriodoNomina;
use App\Models\PlanillaCcss;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlanillaCcssTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected PeriodoNomina $periodoNomina;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;

        $this->periodoNomina = PeriodoNomina::create([
            'empresa_id' => $this->empresa->id,
            'nombre_periodo' => 'Enero 2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-31',
            'estado' => 'Abierto',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    private function datosPlanillaValida(array $overrides = []): array
    {
        return array_merge([
            'periodo_nomina_id' => $this->periodoNomina->id,
            'periodo' => '2026-01',
            'fecha_generacion' => '2026-02-01',
            'total_empleados' => 10,
            'total_salarios' => 5000000.00,
            'total_cuota_obrera' => 475000.00,
            'total_cuota_patronal' => 1325000.00,
            'total_a_pagar' => 1800000.00,
            'estado' => 'borrador',
        ], $overrides);
    }

    private function crearPlanilla(array $overrides = []): PlanillaCcss
    {
        $data = array_merge([
            'periodo_nomina_id' => $this->periodoNomina->id,
            'periodo' => '2026-03',
            'fecha_generacion' => '2026-04-01',
            'total_empleados' => 5,
            'total_salarios' => 2500000.00,
            'total_cuota_obrera' => 237500.00,
            'total_cuota_patronal' => 662500.00,
            'total_a_pagar' => 900000.00,
            'estado' => 'borrador',
            'eliminado' => false,
        ], $overrides);

        $planilla = new PlanillaCcss($data);
        $planilla->empresa_id = $this->empresa->id;
        $planilla->save();
        return $planilla->fresh();
    }

    #[Test]
    public function puede_listar_planillas(): void
    {
        $this->crearPlanilla();

        $response = $this->authenticatedJson('GET', '/api/planillas-ccss', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_planilla(): void
    {
        $response = $this->authenticatedJson('POST', '/api/planillas-ccss', $this->datosPlanillaValida(), $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_planilla(): void
    {
        $planilla = $this->crearPlanilla();

        $response = $this->authenticatedJson('GET', "/api/planillas-ccss/{$planilla->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_planilla(): void
    {
        $planilla = $this->crearPlanilla();

        $response = $this->authenticatedJson('PUT', "/api/planillas-ccss/{$planilla->id}", [
            'notas' => 'Nota actualizada',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_planilla(): void
    {
        $planilla = $this->crearPlanilla();

        $response = $this->authenticatedJson('DELETE', "/api/planillas-ccss/{$planilla->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/planillas-ccss', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['periodo_nomina_id', 'periodo', 'fecha_generacion', 'total_empleados', 'total_salarios']);
    }

    #[Test]
    public function validacion_estado_invalido(): void
    {
        $datos = $this->datosPlanillaValida(['estado' => 'invalido']);

        $response = $this->authenticatedJson('POST', '/api/planillas-ccss', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/planillas-ccss');

        $response->assertUnauthorized();
    }
}
