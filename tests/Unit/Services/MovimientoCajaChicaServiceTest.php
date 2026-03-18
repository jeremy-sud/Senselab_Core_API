<?php

namespace Tests\Unit\Services;

use App\Exceptions\BusinessException;
use App\Models\CajaChica;
use App\Models\Empleado;
use App\Models\MovimientoCajaChica;
use App\Services\MovimientoCajaChicaService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MovimientoCajaChicaServiceTest extends TestCase
{
    protected MovimientoCajaChicaService $service;
    private \App\Models\Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MovimientoCajaChicaService();
        $this->empresa = $this->createEmpresa();
    }

    private function crearCajaChica(array $override = []): CajaChica
    {
        $caja = new CajaChica(array_merge([
            'nombre' => 'Caja Chica ' . uniqid(),
            'monto_inicial' => 50000,
            'saldo_actual' => 50000,
            'fecha_apertura' => now(),
            'estado' => 'Abierta',
        ], $override));
        $caja->empresa_id = $this->empresa->id;
        $caja->save();

        return $caja->fresh();
    }

    private function crearMovimiento(CajaChica $caja, array $override = []): MovimientoCajaChica
    {
        return MovimientoCajaChica::create(array_merge([
            'caja_chica_id' => $caja->id,
            'fecha_movimiento' => now(),
            'tipo_movimiento' => MovimientoCajaChica::TIPO_EGRESO,
            'monto' => 5000,
            'concepto' => 'Movimiento test ' . uniqid(),
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $caja = $this->crearCajaChica();
        $this->crearMovimiento($caja);
        $this->crearMovimiento($caja);

        $resultado = $this->service->listar([], 10);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_excluye_eliminados(): void
    {
        $caja = $this->crearCajaChica();
        $this->crearMovimiento($caja);
        $this->crearMovimiento($caja, ['eliminado' => true, 'activo' => false]);

        $resultado = $this->service->listar();

        foreach ($resultado->items() as $mov) {
            $this->assertFalse((bool) $mov->eliminado);
            $this->assertTrue((bool) $mov->activo);
        }
    }

    #[Test]
    public function listar_filtra_por_caja_chica_id(): void
    {
        $caja1 = $this->crearCajaChica();
        $caja2 = $this->crearCajaChica();
        $this->crearMovimiento($caja1);
        $this->crearMovimiento($caja2);

        $resultado = $this->service->listar(['caja_chica_id' => $caja1->id]);

        foreach ($resultado->items() as $mov) {
            $this->assertEquals($caja1->id, $mov->caja_chica_id);
        }
    }

    #[Test]
    public function listar_filtra_por_tipo_movimiento(): void
    {
        $caja = $this->crearCajaChica();
        $this->crearMovimiento($caja, ['tipo_movimiento' => MovimientoCajaChica::TIPO_EGRESO]);
        $this->crearMovimiento($caja, ['tipo_movimiento' => MovimientoCajaChica::TIPO_INGRESO]);

        $resultado = $this->service->listar(['tipo_movimiento' => MovimientoCajaChica::TIPO_INGRESO]);

        foreach ($resultado->items() as $mov) {
            $this->assertEquals(MovimientoCajaChica::TIPO_INGRESO, $mov->tipo_movimiento);
        }
    }

    #[Test]
    public function listar_filtra_por_rango_fechas(): void
    {
        $caja = $this->crearCajaChica();
        $this->crearMovimiento($caja, ['fecha_movimiento' => '2025-03-15']);
        $this->crearMovimiento($caja, ['fecha_movimiento' => '2025-08-15']);

        $resultado = $this->service->listar([
            'fecha_desde' => '2025-03-01',
            'fecha_hasta' => '2025-03-31',
        ]);

        foreach ($resultado->items() as $mov) {
            $fecha = $mov->fecha_movimiento->format('Y-m-d');
            $this->assertGreaterThanOrEqual('2025-03-01', $fecha);
            $this->assertLessThanOrEqual('2025-03-31', $fecha);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_egreso_exitosamente(): void
    {
        $caja = $this->crearCajaChica(['saldo_actual' => 30000]);

        $movimiento = $this->service->crear([
            'caja_chica_id' => $caja->id,
            'fecha_movimiento' => now()->toDateString(),
            'tipo_movimiento' => MovimientoCajaChica::TIPO_EGRESO,
            'monto' => 10000,
            'concepto' => 'Compra de materiales',
        ]);

        $this->assertInstanceOf(MovimientoCajaChica::class, $movimiento);
        $this->assertEquals(10000, (float) $movimiento->monto);

        $caja->refresh();
        $this->assertEquals(20000, (float) $caja->saldo_actual);
    }

    #[Test]
    public function crear_ingreso_incrementa_saldo(): void
    {
        $caja = $this->crearCajaChica(['saldo_actual' => 10000]);

        $movimiento = $this->service->crear([
            'caja_chica_id' => $caja->id,
            'fecha_movimiento' => now()->toDateString(),
            'tipo_movimiento' => MovimientoCajaChica::TIPO_INGRESO,
            'monto' => 5000,
            'concepto' => 'Reposición de fondos',
        ]);

        $caja->refresh();
        $this->assertEquals(15000, (float) $caja->saldo_actual);
    }

    #[Test]
    public function crear_lanza_excepcion_si_caja_cerrada(): void
    {
        $caja = $this->crearCajaChica(['estado' => 'Cerrada', 'fecha_cierre' => now()]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('fondos abiertos');

        $this->service->crear([
            'caja_chica_id' => $caja->id,
            'fecha_movimiento' => now()->toDateString(),
            'tipo_movimiento' => MovimientoCajaChica::TIPO_EGRESO,
            'monto' => 1000,
            'concepto' => 'Test',
        ]);
    }

    #[Test]
    public function crear_lanza_excepcion_si_saldo_insuficiente(): void
    {
        $caja = $this->crearCajaChica(['saldo_actual' => 500]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('Saldo insuficiente');

        $this->service->crear([
            'caja_chica_id' => $caja->id,
            'fecha_movimiento' => now()->toDateString(),
            'tipo_movimiento' => MovimientoCajaChica::TIPO_EGRESO,
            'monto' => 1000,
            'concepto' => 'Test excede saldo',
        ]);
    }

    #[Test]
    public function crear_carga_relaciones(): void
    {
        $caja = $this->crearCajaChica();

        $movimiento = $this->service->crear([
            'caja_chica_id' => $caja->id,
            'fecha_movimiento' => now()->toDateString(),
            'tipo_movimiento' => MovimientoCajaChica::TIPO_INGRESO,
            'monto' => 1000,
            'concepto' => 'Test relaciones',
        ]);

        $this->assertTrue($movimiento->relationLoaded('cajaChica'));
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_egreso_revierte_saldo(): void
    {
        $caja = $this->crearCajaChica(['saldo_actual' => 30000]);

        $movimiento = MovimientoCajaChica::create([
            'caja_chica_id' => $caja->id,
            'fecha_movimiento' => now(),
            'tipo_movimiento' => MovimientoCajaChica::TIPO_EGRESO,
            'monto' => 10000,
            'concepto' => 'Gasto a eliminar',
        ]);

        // Simular el efecto de un egreso previo
        $caja->decrement('saldo_actual', 10000);
        $caja->refresh();
        $this->assertEquals(20000, (float) $caja->saldo_actual);

        $result = $this->service->eliminar($movimiento);

        $this->assertTrue($result);
        $caja->refresh();
        $this->assertEquals(30000, (float) $caja->saldo_actual);
        $this->assertDatabaseHas('movimientos_caja_chica', [
            'id' => $movimiento->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }

    #[Test]
    public function eliminar_ingreso_decrementa_saldo(): void
    {
        $caja = $this->crearCajaChica(['saldo_actual' => 30000]);

        $movimiento = MovimientoCajaChica::create([
            'caja_chica_id' => $caja->id,
            'fecha_movimiento' => now(),
            'tipo_movimiento' => MovimientoCajaChica::TIPO_INGRESO,
            'monto' => 5000,
            'concepto' => 'Ingreso a eliminar',
        ]);

        // Simular el efecto de un ingreso previo
        $caja->increment('saldo_actual', 5000);
        $caja->refresh();
        $this->assertEquals(35000, (float) $caja->saldo_actual);

        $this->service->eliminar($movimiento);

        $caja->refresh();
        $this->assertEquals(30000, (float) $caja->saldo_actual);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_movimiento_existente(): void
    {
        $caja = $this->crearCajaChica();
        $movimiento = $this->crearMovimiento($caja);

        $resultado = $this->service->obtener($movimiento->id);

        $this->assertEquals($movimiento->id, $resultado->id);
    }
}
