<?php

namespace Tests\Unit\Services;

use App\Exceptions\BusinessException;
use App\Models\CuentaPorPagar;
use App\Models\FormaPago;
use App\Models\PagoCuentaPagar;
use App\Models\Proveedor;
use App\Services\PagoCuentaPagarService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PagoCuentaPagarServiceTest extends TestCase
{
    protected PagoCuentaPagarService $service;
    private \App\Models\Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PagoCuentaPagarService();
        $this->empresa = $this->createEmpresa();
    }

    private function crearCuentaPorPagar(array $override = []): CuentaPorPagar
    {
        $proveedor = Proveedor::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => 'CED',
            'numero_identificacion' => uniqid(),
            'nombre' => 'Proveedor Test ' . uniqid(),
        ]);

        $cuenta = new CuentaPorPagar(array_merge([
            'proveedor_id' => $proveedor->id,
            'numero_documento' => 'DOC-' . uniqid(),
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays(30),
            'monto_original' => 100000,
            'monto_pendiente' => 100000,
            'monto_pagado' => 0,
            'estado' => 'Pendiente',
            'moneda' => 'CRC',
        ], $override));
        $cuenta->empresa_id = $this->empresa->id;
        $cuenta->saveQuietly();

        return $cuenta->fresh();
    }

    private function crearPago(CuentaPorPagar $cuenta, array $override = []): PagoCuentaPagar
    {
        $formaPago = $this->getFormaPago();

        return PagoCuentaPagar::create(array_merge([
            'cuenta_por_pagar_id' => $cuenta->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now(),
            'monto_pago' => 10000,
            'moneda' => 'CRC',
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $cuenta = $this->crearCuentaPorPagar();
        $this->crearPago($cuenta);
        $this->crearPago($cuenta);

        $resultado = $this->service->listar([], 10);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_excluye_eliminados(): void
    {
        $cuenta = $this->crearCuentaPorPagar();
        $this->crearPago($cuenta);
        $this->crearPago($cuenta, ['eliminado' => true, 'activo' => false]);

        $resultado = $this->service->listar();

        foreach ($resultado->items() as $pago) {
            $this->assertFalse((bool) $pago->eliminado);
        }
    }

    #[Test]
    public function listar_filtra_por_cuenta_por_pagar_id(): void
    {
        $cuenta1 = $this->crearCuentaPorPagar();
        $cuenta2 = $this->crearCuentaPorPagar();
        $this->crearPago($cuenta1);
        $this->crearPago($cuenta2);

        $resultado = $this->service->listar(['cuenta_por_pagar_id' => $cuenta1->id]);

        foreach ($resultado->items() as $pago) {
            $this->assertEquals($cuenta1->id, $pago->cuenta_por_pagar_id);
        }
    }

    #[Test]
    public function listar_filtra_por_forma_pago_id(): void
    {
        $cuenta = $this->crearCuentaPorPagar();
        $formaPago = $this->getFormaPago();
        $this->crearPago($cuenta, ['forma_pago_id' => $formaPago->id]);

        $resultado = $this->service->listar(['forma_pago_id' => $formaPago->id]);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
        foreach ($resultado->items() as $pago) {
            $this->assertEquals($formaPago->id, $pago->forma_pago_id);
        }
    }

    #[Test]
    public function listar_filtra_por_rango_fechas(): void
    {
        $cuenta = $this->crearCuentaPorPagar();
        $this->crearPago($cuenta, ['fecha_pago' => '2025-01-15']);
        $this->crearPago($cuenta, ['fecha_pago' => '2025-06-15']);

        $resultado = $this->service->listar([
            'fecha_desde' => '2025-01-01',
            'fecha_hasta' => '2025-01-31',
        ]);

        foreach ($resultado->items() as $pago) {
            $this->assertGreaterThanOrEqual('2025-01-01', $pago->fecha_pago->format('Y-m-d'));
            $this->assertLessThanOrEqual('2025-01-31', $pago->fecha_pago->format('Y-m-d'));
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_pago_exitosamente(): void
    {
        $cuenta = $this->crearCuentaPorPagar(['monto_original' => 50000, 'monto_pagado' => 0]);
        $formaPago = $this->getFormaPago();

        $pago = $this->service->crear([
            'cuenta_por_pagar_id' => $cuenta->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now()->toDateString(),
            'monto_pago' => 20000,
            'moneda' => 'CRC',
        ]);

        $this->assertInstanceOf(PagoCuentaPagar::class, $pago);
        $this->assertEquals(20000, (float) $pago->monto_pago);
        $this->assertDatabaseHas('pagos_cuentas_pagar', ['id' => $pago->id]);

        // Verifica que se incrementó monto_pagado
        $cuenta->refresh();
        $this->assertEquals(20000, (float) $cuenta->monto_pagado);
    }

    #[Test]
    public function crear_pago_lanza_excepcion_si_monto_excede_saldo(): void
    {
        $cuenta = $this->crearCuentaPorPagar(['monto_original' => 10000, 'monto_pagado' => 8000]);
        $formaPago = $this->getFormaPago();

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('excede el saldo pendiente');

        $this->service->crear([
            'cuenta_por_pagar_id' => $cuenta->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now()->toDateString(),
            'monto_pago' => 5000,
            'moneda' => 'CRC',
        ]);
    }

    #[Test]
    public function crear_carga_relaciones(): void
    {
        $cuenta = $this->crearCuentaPorPagar();
        $formaPago = $this->getFormaPago();

        $pago = $this->service->crear([
            'cuenta_por_pagar_id' => $cuenta->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now()->toDateString(),
            'monto_pago' => 5000,
            'moneda' => 'CRC',
        ]);

        $this->assertTrue($pago->relationLoaded('cuentaPorPagar'));
        $this->assertTrue($pago->relationLoaded('formaPago'));
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_pago_revierte_monto_pagado(): void
    {
        $cuenta = $this->crearCuentaPorPagar(['monto_original' => 50000, 'monto_pagado' => 0]);
        $formaPago = $this->getFormaPago();

        $pago = PagoCuentaPagar::create([
            'cuenta_por_pagar_id' => $cuenta->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now(),
            'monto_pago' => 15000,
            'moneda' => 'CRC',
        ]);

        $cuenta->increment('monto_pagado', 15000);
        $cuenta->refresh();
        $this->assertEquals(15000, (float) $cuenta->monto_pagado);

        $result = $this->service->eliminar($pago);

        $this->assertTrue($result);
        $cuenta->refresh();
        $this->assertEquals(0, (float) $cuenta->monto_pagado);
        $this->assertDatabaseHas('pagos_cuentas_pagar', [
            'id' => $pago->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_pago_existente(): void
    {
        $cuenta = $this->crearCuentaPorPagar();
        $pago = $this->crearPago($cuenta);

        $resultado = $this->service->obtener($pago->id);

        $this->assertEquals($pago->id, $resultado->id);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_pago_exitosamente(): void
    {
        $cuenta = $this->crearCuentaPorPagar();
        $pago = $this->crearPago($cuenta);

        $resultado = $this->service->actualizar($pago, ['observaciones' => 'Nota actualizada']);

        $this->assertEquals('Nota actualizada', $resultado->observaciones);
    }
}
