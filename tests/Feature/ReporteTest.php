<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReporteTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->empresa = $this->createEmpresa();
    }

    public function test_obtener_reporte_financiero_estado_resultados(): void
    {
        $response = $this->authenticatedJson('GET', '/api/reportes/financiero', [
            'tipo' => 'estado_resultados',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tipo_reporte',
                    'periodo',
                    'moneda',
                    'totales',
                ],
            ]);
    }

    public function test_obtener_reporte_balance_general(): void
    {
        $response = $this->authenticatedJson('GET', '/api/reportes/financiero', [
            'tipo' => 'balance_general',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.tipo_reporte', 'balance_general');
    }

    public function test_obtener_reporte_flujo_caja(): void
    {
        $response = $this->authenticatedJson('GET', '/api/reportes/financiero', [
            'tipo' => 'flujo_caja',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.tipo_reporte', 'flujo_caja');
    }

    public function test_validacion_tipo_reporte_invalido(): void
    {
        $response = $this->authenticatedJson('GET', '/api/reportes/financiero', [
            'tipo' => 'invalido',
        ]);

        $response->assertStatus(422);
    }

    public function test_validacion_fecha_inicio_posterior_a_fin(): void
    {
        $response = $this->authenticatedJson('GET', '/api/reportes/financiero', [
            'fecha_inicio' => '2026-12-31',
            'fecha_fin' => '2026-01-01',
        ]);

        $response->assertStatus(422);
    }

    public function test_validacion_moneda_invalida(): void
    {
        $response = $this->authenticatedJson('GET', '/api/reportes/financiero', [
            'moneda' => 'GBP',
        ]);

        $response->assertStatus(422);
    }

    public function test_listar_tipos_disponibles(): void
    {
        $response = $this->authenticatedJson('GET', '/api/reportes/tipos');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_invalidar_cache_reportes(): void
    {
        $response = $this->authenticatedJson('POST', '/api/reportes/invalidar-cache');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_reporte_sin_autenticacion(): void
    {
        $response = $this->getJson('/api/reportes/financiero');

        $response->assertStatus(401);
    }

    public function test_reporte_con_comparacion_mes(): void
    {
        $response = $this->authenticatedJson('GET', '/api/reportes/financiero', [
            'tipo' => 'estado_resultados',
            'periodo_comparacion' => 'mes',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'comparacion' => [
                        'periodo_anterior',
                        'datos',
                    ],
                ],
            ]);
    }

    public function test_reporte_con_filtro_sucursal(): void
    {
        $sucursal = $this->createSucursal($this->empresa);

        $response = $this->authenticatedJson('GET', '/api/reportes/financiero', [
            'tipo' => 'flujo_caja',
            'sucursal_id' => $sucursal->id,
        ]);

        $response->assertStatus(200);
    }
}
