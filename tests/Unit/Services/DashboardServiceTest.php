<?php

namespace Tests\Unit\Services;

use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Models\Empresa;
use App\Models\InventarioProducto;
use App\Models\NominaEmpleado;
use App\Models\PeriodoNomina;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;
    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardService();
        $this->empresa = $this->createEmpresa();
    }

    public function test_obtener_kpis_sin_datos(): void
    {
        $kpis = $this->service->obtenerKpis($this->empresa->id);

        $this->assertArrayHasKey('ventas_mes', $kpis);
        $this->assertArrayHasKey('cuentas_vencidas', $kpis);
        $this->assertArrayHasKey('inventario_bajo_minimo', $kpis);
        $this->assertArrayHasKey('nomina_pendiente', $kpis);
        $this->assertArrayHasKey('resumen_financiero', $kpis);
        $this->assertArrayHasKey('generado_en', $kpis);
    }

    public function test_ventas_del_mes_con_datos(): void
    {
        $sucursal = $this->createSucursal($this->empresa);
        $usuario = $this->createUsuario(['empresa_id' => $this->empresa->id]);
        $formaPago = $this->getFormaPago();

        Venta::create([
            'empresa_id' => $this->empresa->id,
            'sucursal_id' => $sucursal->id,
            'usuario_id' => $usuario->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_venta' => now(),
            'monto_total_venta' => 50000.00,
            'estado_venta' => 'completada',
            'activo' => true,
            'eliminado' => false,
        ]);

        $kpis = $this->service->obtenerKpis($this->empresa->id);

        $this->assertEquals(1, $kpis['ventas_mes']['cantidad']);
        $this->assertEquals('50000.00', number_format((float) $kpis['ventas_mes']['total'], 2, '.', ''));
    }

    public function test_obtener_kpis_con_filtro_sucursal(): void
    {
        $sucursal = $this->createSucursal($this->empresa);

        $kpis = $this->service->obtenerKpis($this->empresa->id, $sucursal->id);

        $this->assertArrayHasKey('ventas_mes', $kpis);
        $this->assertEquals(0, $kpis['ventas_mes']['cantidad']);
    }

    public function test_nomina_pendiente_sin_periodos(): void
    {
        $kpis = $this->service->obtenerKpis($this->empresa->id);

        $this->assertFalse($kpis['nomina_pendiente']['periodo_pendiente']);
        $this->assertEquals('0.00', $kpis['nomina_pendiente']['total_neto']);
    }

    public function test_inventario_bajo_minimo(): void
    {
        $kpis = $this->service->obtenerKpis($this->empresa->id);

        $this->assertEquals(0, $kpis['inventario_bajo_minimo']['cantidad']);
        $this->assertIsArray($kpis['inventario_bajo_minimo']['productos']);
    }

    public function test_invalidar_cache(): void
    {
        $this->service->obtenerKpis($this->empresa->id);
        $this->service->invalidarCache($this->empresa->id);

        // Should rebuild cache
        $kpis = $this->service->obtenerKpis($this->empresa->id);
        $this->assertArrayHasKey('generado_en', $kpis);
    }

    public function test_cuentas_vencidas(): void
    {
        $kpis = $this->service->obtenerKpis($this->empresa->id);

        $this->assertArrayHasKey('cuentas_por_cobrar', $kpis['cuentas_vencidas']);
        $this->assertArrayHasKey('cuentas_por_pagar', $kpis['cuentas_vencidas']);
        $this->assertEquals(0, $kpis['cuentas_vencidas']['cuentas_por_cobrar']['cantidad']);
    }

    public function test_resumen_financiero(): void
    {
        $kpis = $this->service->obtenerKpis($this->empresa->id);

        $this->assertArrayHasKey('ingresos_mes', $kpis['resumen_financiero']);
        $this->assertArrayHasKey('cuentas_por_cobrar_pendientes', $kpis['resumen_financiero']);
        $this->assertArrayHasKey('cuentas_por_pagar_pendientes', $kpis['resumen_financiero']);
        $this->assertArrayHasKey('posicion_neta', $kpis['resumen_financiero']);
    }
}
