<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\ReporteProgramado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_obtener_dashboard_kpis(): void
    {
        $response = $this->authenticatedJson('GET', '/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'ventas_mes',
                    'cuentas_vencidas',
                    'inventario_bajo_minimo',
                    'nomina_pendiente',
                    'resumen_financiero',
                    'generado_en',
                ],
            ]);
    }

    public function test_dashboard_con_filtro_sucursal(): void
    {
        $sucursal = $this->createSucursal($this->empresa);

        $response = $this->authenticatedJson('GET', '/api/dashboard', [
            'sucursal_id' => $sucursal->id,
        ]);

        $response->assertStatus(200);
    }

    public function test_invalidar_cache_dashboard(): void
    {
        $response = $this->authenticatedJson('POST', '/api/dashboard/invalidar-cache');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_dashboard_sin_autenticacion(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(401);
    }

    // ======= REPORTES PROGRAMADOS CRUD =======

    public function test_listar_reportes_programados(): void
    {
        $response = $this->authenticatedJson('GET', '/api/reportes/programados');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_crear_reporte_programado(): void
    {
        $response = $this->authenticatedJson('POST', '/api/reportes/programados', [
            'nombre' => 'Reporte Mensual Ventas',
            'tipo_reporte' => 'estado_resultados',
            'frecuencia' => 'mensual',
            'formato' => 'pdf',
            'destinatarios' => ['admin@empresa.com'],
            'dia_mes' => 1,
            'hora_envio' => '08:00',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nombre', 'Reporte Mensual Ventas');
    }

    public function test_crear_reporte_programado_validacion_falla(): void
    {
        $response = $this->authenticatedJson('POST', '/api/reportes/programados', [
            'nombre' => 'Test',
            'tipo_reporte' => 'invalido',
            'frecuencia' => 'diario',
            'destinatarios' => ['test@test.com'],
        ]);

        $response->assertStatus(422);
    }

    public function test_crear_reporte_semanal_requiere_dia_semana(): void
    {
        $response = $this->authenticatedJson('POST', '/api/reportes/programados', [
            'nombre' => 'Reporte Semanal',
            'tipo_reporte' => 'balance_general',
            'frecuencia' => 'semanal',
            'destinatarios' => ['admin@empresa.com'],
            // Missing: dia_semana
        ]);

        $response->assertStatus(422);
    }

    public function test_ver_reporte_programado(): void
    {
        $usuario = $this->createAdminUsuario();
        $reporte = ReporteProgramado::create([
            'empresa_id' => $usuario->empresa_id,
            'usuario_id' => $usuario->id,
            'nombre' => 'Test Reporte',
            'tipo_reporte' => 'estado_resultados',
            'frecuencia' => 'diario',
            'formato' => 'pdf',
            'destinatarios' => ['test@test.com'],
            'hora_envio' => '07:00',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', "/api/reportes/programados/{$reporte->id}", usuario: $usuario);

        $response->assertStatus(200)
            ->assertJsonPath('data.nombre', 'Test Reporte');
    }

    public function test_actualizar_reporte_programado(): void
    {
        $usuario = $this->createAdminUsuario();
        $reporte = ReporteProgramado::create([
            'empresa_id' => $usuario->empresa_id,
            'usuario_id' => $usuario->id,
            'nombre' => 'Test Original',
            'tipo_reporte' => 'estado_resultados',
            'frecuencia' => 'diario',
            'formato' => 'pdf',
            'destinatarios' => ['test@test.com'],
            'hora_envio' => '07:00',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('PUT', "/api/reportes/programados/{$reporte->id}", [
            'nombre' => 'Test Actualizado',
            'frecuencia' => 'semanal',
            'dia_semana' => 'lunes',
        ], usuario: $usuario);

        $response->assertStatus(200)
            ->assertJsonPath('data.nombre', 'Test Actualizado');
    }

    public function test_eliminar_reporte_programado(): void
    {
        $usuario = $this->createAdminUsuario();
        $reporte = ReporteProgramado::create([
            'empresa_id' => $usuario->empresa_id,
            'usuario_id' => $usuario->id,
            'nombre' => 'Para eliminar',
            'tipo_reporte' => 'flujo_caja',
            'frecuencia' => 'diario',
            'formato' => 'csv',
            'destinatarios' => ['test@test.com'],
            'hora_envio' => '07:00',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/reportes/programados/{$reporte->id}", usuario: $usuario);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reportes_programados', [
            'id' => $reporte->id,
            'eliminado' => true,
        ]);
    }

    public function test_validacion_destinatarios_maximo(): void
    {
        $emails = [];
        for ($i = 0; $i < 11; $i++) {
            $emails[] = "test{$i}@test.com";
        }

        $response = $this->authenticatedJson('POST', '/api/reportes/programados', [
            'nombre' => 'Muchos destinatarios',
            'tipo_reporte' => 'estado_resultados',
            'frecuencia' => 'diario',
            'destinatarios' => $emails,
        ]);

        $response->assertStatus(422);
    }
}
