<?php

namespace Tests\Unit\Services;

use App\DTOs\API\ReportFilterDTO;
use App\Models\AsientoContable;
use App\Models\CuentaContable;
use App\Models\DetalleAsiento;
use App\Models\Empresa;
use App\Models\TipoCuenta;
use App\Services\ReportingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReportingService $service;
    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportingService();
        $this->empresa = $this->createEmpresa();
    }

    public function test_estado_resultados_sin_datos(): void
    {
        $filtro = new ReportFilterDTO(
            empresaId: $this->empresa->id,
            tipoReporte: 'estado_resultados',
            fechaInicio: now()->startOfMonth()->toDateString(),
            fechaFin: now()->toDateString(),
        );

        $resultado = $this->service->estadoResultados($filtro);

        $this->assertEquals('estado_resultados', $resultado['tipo_reporte']);
        $this->assertArrayHasKey('totales', $resultado);
        $this->assertEquals('0.00', $resultado['totales']['total_ingresos']);
        $this->assertEquals('0.00', $resultado['totales']['total_gastos']);
        $this->assertEquals('0.00', $resultado['totales']['utilidad_neta']);
    }

    public function test_estado_resultados_con_datos(): void
    {
        $this->crearDatosContables();

        $filtro = new ReportFilterDTO(
            empresaId: $this->empresa->id,
            tipoReporte: 'estado_resultados',
            fechaInicio: now()->subMonth()->toDateString(),
            fechaFin: now()->addDay()->toDateString(),
        );

        $resultado = $this->service->estadoResultados($filtro);

        $this->assertEquals('estado_resultados', $resultado['tipo_reporte']);
        $this->assertArrayHasKey('ingresos', $resultado);
        $this->assertArrayHasKey('gastos_operativos', $resultado);
        $this->assertArrayHasKey('totales', $resultado);
    }

    public function test_balance_general_sin_datos(): void
    {
        $filtro = new ReportFilterDTO(
            empresaId: $this->empresa->id,
            tipoReporte: 'balance_general',
            fechaInicio: now()->startOfYear()->toDateString(),
            fechaFin: now()->toDateString(),
        );

        $resultado = $this->service->balanceGeneral($filtro);

        $this->assertEquals('balance_general', $resultado['tipo_reporte']);
        $this->assertArrayHasKey('activos', $resultado);
        $this->assertArrayHasKey('pasivos', $resultado);
        $this->assertArrayHasKey('capital', $resultado);
        $this->assertArrayHasKey('totales', $resultado);
    }

    public function test_flujo_caja_sin_datos(): void
    {
        $filtro = new ReportFilterDTO(
            empresaId: $this->empresa->id,
            tipoReporte: 'flujo_caja',
            fechaInicio: now()->startOfMonth()->toDateString(),
            fechaFin: now()->toDateString(),
        );

        $resultado = $this->service->flujoCaja($filtro);

        $this->assertEquals('flujo_caja', $resultado['tipo_reporte']);
        $this->assertArrayHasKey('actividades_operativas', $resultado);
        $this->assertArrayHasKey('totales', $resultado);
    }

    public function test_generar_tipo_invalido_lanza_excepcion(): void
    {
        $filtro = new ReportFilterDTO(
            empresaId: $this->empresa->id,
            tipoReporte: 'invalido',
            fechaInicio: now()->toDateString(),
            fechaFin: now()->toDateString(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Tipo de reporte no soportado: invalido');

        $this->service->generar($filtro);
    }

    public function test_generar_estado_resultados_via_metodo_general(): void
    {
        $filtro = new ReportFilterDTO(
            empresaId: $this->empresa->id,
            tipoReporte: 'estado_resultados',
            fechaInicio: now()->startOfMonth()->toDateString(),
            fechaFin: now()->toDateString(),
        );

        $resultado = $this->service->generar($filtro);

        $this->assertEquals('estado_resultados', $resultado['tipo_reporte']);
    }

    public function test_estado_resultados_con_comparacion(): void
    {
        $filtro = new ReportFilterDTO(
            empresaId: $this->empresa->id,
            tipoReporte: 'estado_resultados',
            fechaInicio: now()->startOfMonth()->toDateString(),
            fechaFin: now()->toDateString(),
            periodoComparacion: 'mes',
        );

        $resultado = $this->service->estadoResultados($filtro);

        $this->assertArrayHasKey('comparacion', $resultado);
        $this->assertArrayHasKey('periodo_anterior', $resultado['comparacion']);
        $this->assertArrayHasKey('datos', $resultado['comparacion']);
    }

    public function test_flujo_caja_con_filtro_sucursal(): void
    {
        $sucursal = $this->createSucursal($this->empresa);

        $filtro = new ReportFilterDTO(
            empresaId: $this->empresa->id,
            tipoReporte: 'flujo_caja',
            fechaInicio: now()->startOfMonth()->toDateString(),
            fechaFin: now()->toDateString(),
            sucursalId: $sucursal->id,
        );

        $resultado = $this->service->flujoCaja($filtro);

        $this->assertEquals('flujo_caja', $resultado['tipo_reporte']);
        $this->assertEquals('CRC', $resultado['moneda']);
    }

    public function test_invalidar_cache(): void
    {
        // Generate a report to populate cache
        $filtro = new ReportFilterDTO(
            empresaId: $this->empresa->id,
            tipoReporte: 'estado_resultados',
            fechaInicio: now()->startOfMonth()->toDateString(),
            fechaFin: now()->toDateString(),
        );
        $this->service->estadoResultados($filtro);

        // Should not throw
        $this->service->invalidarCache($this->empresa->id);
        $this->assertTrue(true);
    }

    private function crearDatosContables(): void
    {
        $usuario = $this->createUsuario(['empresa_id' => $this->empresa->id]);

        // Crear tipos de cuenta (naturaleza solo acepta 'Deudora' o 'Acreedora')
        $tipoIngreso = TipoCuenta::create([
            'nombre' => 'Ingresos',
            'descripcion' => 'Cuentas de ingresos',
            'naturaleza' => 'Acreedora',
            'activo' => true,
            'eliminado' => false,
        ]);

        $tipoGasto = TipoCuenta::create([
            'nombre' => 'Gastos',
            'descripcion' => 'Cuentas de gastos',
            'naturaleza' => 'Deudora',
            'activo' => true,
            'eliminado' => false,
        ]);

        // Crear cuentas contables
        $cuentaIngreso = CuentaContable::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Ventas',
            'codigo' => '4-01-001',
            'tipo_cuenta_id' => $tipoIngreso->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        $cuentaGasto = CuentaContable::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Gastos Operativos',
            'codigo' => '5-01-001',
            'tipo_cuenta_id' => $tipoGasto->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Crear asiento contable
        $asiento = AsientoContable::create([
            'empresa_id' => $this->empresa->id,
            'numero_asiento' => 1,
            'fecha_asiento' => now()->toDateString(),
            'tipo_asiento' => 'Manual',
            'total_debe' => 1000.00,
            'total_haber' => 1000.00,
            'estado' => 'Confirmado',
            'concepto' => 'Asiento de prueba',
            'usuario_id' => $usuario->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Detalle: ingreso (haber)
        DetalleAsiento::create([
            'asiento_contable_id' => $asiento->id,
            'cuenta_contable_id' => $cuentaIngreso->id,
            'debe' => 0,
            'haber' => 1000.00,
            'descripcion' => 'Ingreso por ventas',
            'activo' => true,
            'eliminado' => false,
        ]);

        // Detalle: gasto (debe)
        DetalleAsiento::create([
            'asiento_contable_id' => $asiento->id,
            'cuenta_contable_id' => $cuentaGasto->id,
            'debe' => 1000.00,
            'haber' => 0,
            'descripcion' => 'Gasto operativo',
            'activo' => true,
            'eliminado' => false,
        ]);
    }
}
