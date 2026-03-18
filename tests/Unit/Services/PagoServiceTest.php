<?php

namespace Tests\Unit\Services;

use App\Exceptions\BusinessException;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Models\Cliente;
use App\Models\FormaPago;
use App\Models\Pago;
use App\Models\Proveedor;
use App\Services\PagoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PagoServiceTest extends TestCase
{
    protected PagoService $service;
    private \App\Models\Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PagoService();
        $this->empresa = $this->createEmpresa();
    }

    private function nuevaEmpresa(): \App\Models\Empresa
    {
        return $this->createEmpresa(['email' => uniqid() . '@empresa.com']);
    }

    private function crearPago(array $override = []): Pago
    {
        $empresa = $override['_empresa'] ?? $this->empresa;
        unset($override['_empresa']);
        $formaPago = $this->getFormaPago();

        return Pago::create(array_merge([
            'empresa_id' => $empresa->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now(),
            'monto' => 10000,
            'estado' => 'pendiente',
            'moneda' => 'CRC',
        ], $override));
    }

    private function crearCuentaPorPagar(array $override = []): CuentaPorPagar
    {
        $empresa = $override['_empresa'] ?? $this->empresa;
        unset($override['_empresa']);
        $proveedor = Proveedor::create([
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => 'CED',
            'numero_identificacion' => uniqid(),
            'nombre' => 'Proveedor Test',
        ]);

        $cuenta = new CuentaPorPagar(array_merge([
            'proveedor_id' => $proveedor->id,
            'numero_documento' => 'CP-' . uniqid(),
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays(30),
            'monto_original' => 100000,
            'monto_pendiente' => 100000,
            'monto_pagado' => 0,
            'estado' => 'Pendiente',
            'moneda' => 'CRC',
        ], $override));
        $cuenta->empresa_id = $empresa->id;
        $cuenta->saveQuietly();

        return $cuenta->fresh();
    }

    private function crearCuentaPorCobrar(array $override = []): CuentaPorCobrar
    {
        $empresa = $override['_empresa'] ?? $this->empresa;
        unset($override['_empresa']);
        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => 'CED',
            'numero_identificacion' => uniqid(),
            'nombre' => 'Cliente Test',
            'apellidos' => 'Apellido Test',
        ]);

        $cuenta = new CuentaPorCobrar(array_merge([
            'cliente_id' => $cliente->id,
            'numero_documento' => 'CC-' . uniqid(),
            'fecha_emision' => now(),
            'fecha_vencimiento' => now()->addDays(30),
            'monto_original' => 100000,
            'monto_pendiente' => 100000,
            'monto_pagado' => 0,
            'estado' => 'Pendiente',
            'moneda' => 'CRC',
        ], $override));
        $cuenta->empresa_id = $empresa->id;
        $cuenta->saveQuietly();

        return $cuenta->fresh();
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $this->crearPago();
        $this->crearPago();

        $resultado = $this->service->listar([], 10);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_excluye_eliminados(): void
    {
        $this->crearPago();
        $this->crearPago(['eliminado' => true]);

        $resultado = $this->service->listar();

        foreach ($resultado->items() as $pago) {
            $this->assertFalse((bool) $pago->eliminado);
        }
    }

    #[Test]
    public function listar_filtra_por_empresa_id(): void
    {
        $empresa2 = $this->nuevaEmpresa();
        $this->crearPago();
        $this->crearPago(['_empresa' => $empresa2]);

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id]);

        foreach ($resultado->items() as $pago) {
            $this->assertEquals($this->empresa->id, $pago->empresa_id);
        }
    }

    #[Test]
    public function listar_filtra_por_estado(): void
    {
        $this->crearPago(['estado' => 'pendiente']);
        $this->crearPago(['estado' => 'confirmado']);

        $resultado = $this->service->listar(['estado' => 'pendiente']);

        foreach ($resultado->items() as $pago) {
            $this->assertEquals('pendiente', $pago->estado);
        }
    }

    #[Test]
    public function listar_filtra_por_forma_pago_id(): void
    {
        $formaPago = $this->getFormaPago();
        $this->crearPago(['forma_pago_id' => $formaPago->id]);

        $resultado = $this->service->listar(['forma_pago_id' => $formaPago->id]);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
    }

    #[Test]
    public function listar_filtra_por_rango_fechas(): void
    {
        $this->crearPago(['fecha_pago' => '2025-03-01']);
        $this->crearPago(['fecha_pago' => '2025-09-01']);

        $resultado = $this->service->listar([
            'desde' => '2025-03-01',
            'hasta' => '2025-03-31',
        ]);

        foreach ($resultado->items() as $pago) {
            $fecha = $pago->fecha_pago->format('Y-m-d');
            $this->assertGreaterThanOrEqual('2025-03-01', $fecha);
            $this->assertLessThanOrEqual('2025-03-31', $fecha);
        }
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_pago_existente(): void
    {
        $pago = $this->crearPago();

        $resultado = $this->service->obtener($pago->id);

        $this->assertEquals($pago->id, $resultado->id);
    }

    #[Test]
    public function obtener_pago_eliminado_lanza_excepcion(): void
    {
        $pago = $this->crearPago(['eliminado' => true]);

        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener($pago->id);
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_pago_simple_exitosamente(): void
    {
        $formaPago = $this->getFormaPago();

        $pago = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now()->toDateTimeString(),
            'monto' => 25000,
            'estado' => 'pendiente',
            'moneda' => 'CRC',
        ]);

        $this->assertInstanceOf(Pago::class, $pago);
        $this->assertEquals(25000, (float) $pago->monto);
        $this->assertDatabaseHas('pagos', ['id' => $pago->id]);
    }

    #[Test]
    public function crear_pago_con_cuenta_por_pagar_actualiza_saldo(): void
    {
        $cuenta = $this->crearCuentaPorPagar(['monto_original' => 50000, 'monto_pagado' => 0]);
        $formaPago = $this->getFormaPago();

        $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now()->toDateTimeString(),
            'monto' => 20000,
            'estado' => 'pendiente',
            'moneda' => 'CRC',
            'cuenta_por_pagar_id' => $cuenta->id,
        ]);

        $cuenta->refresh();
        $this->assertEquals(20000, (float) $cuenta->monto_pagado);
    }

    #[Test]
    public function crear_pago_con_cuenta_por_cobrar_actualiza_saldo(): void
    {
        $cuenta = $this->crearCuentaPorCobrar(['monto_original' => 50000, 'monto_pagado' => 0]);
        $formaPago = $this->getFormaPago();

        $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now()->toDateTimeString(),
            'monto' => 15000,
            'estado' => 'pendiente',
            'moneda' => 'CRC',
            'cuenta_por_cobrar_id' => $cuenta->id,
        ]);

        $cuenta->refresh();
        $this->assertEquals(15000, (float) $cuenta->monto_pagado);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_pago_exitosamente(): void
    {
        $pago = $this->crearPago(['estado' => 'pendiente']);

        $resultado = $this->service->actualizar($pago, ['descripcion' => 'Actualizado']);

        $this->assertEquals('Actualizado', $resultado->descripcion);
    }

    #[Test]
    public function actualizar_pago_pagado_lanza_excepcion(): void
    {
        $pago = $this->crearPago(['estado' => 'pagado']);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('No se puede modificar un pago ya procesado');

        $this->service->actualizar($pago, ['descripcion' => 'Cambio']);
    }

    #[Test]
    public function actualizar_monto_ajusta_cuenta_por_pagar(): void
    {
        $cuenta = $this->crearCuentaPorPagar(['monto_original' => 100000, 'monto_pagado' => 20000]);
        $formaPago = $this->getFormaPago();

        $pago = Pago::create([
            'empresa_id' => $this->empresa->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now(),
            'monto' => 20000,
            'estado' => 'pendiente',
            'moneda' => 'CRC',
            'cuenta_por_pagar_id' => $cuenta->id,
        ]);

        $this->service->actualizar($pago, ['monto' => 30000]);

        $cuenta->refresh();
        // Diferencia: 30000 - 20000 = 10000 incrementado
        $this->assertEquals(30000, (float) $cuenta->monto_pagado);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_pago_pendiente_exitosamente(): void
    {
        $pago = $this->crearPago(['estado' => 'pendiente']);

        $resultado = $this->service->eliminar($pago);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('pagos', [
            'id' => $pago->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }

    #[Test]
    public function eliminar_pago_pagado_lanza_excepcion(): void
    {
        $pago = $this->crearPago(['estado' => 'pagado']);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('No se puede eliminar un pago ya procesado');

        $this->service->eliminar($pago);
    }

    #[Test]
    public function eliminar_pago_revierte_cuenta_por_pagar(): void
    {
        $cuenta = $this->crearCuentaPorPagar(['monto_original' => 50000, 'monto_pagado' => 15000]);
        $formaPago = $this->getFormaPago();

        $pago = Pago::create([
            'empresa_id' => $this->empresa->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now(),
            'monto' => 15000,
            'estado' => 'pendiente',
            'moneda' => 'CRC',
            'cuenta_por_pagar_id' => $cuenta->id,
        ]);

        $this->service->eliminar($pago);

        $cuenta->refresh();
        $this->assertEquals(0, (float) $cuenta->monto_pagado);
    }

    // ─── resumenPorFormaPago() ──────────────────────────────────

    #[Test]
    public function resumen_por_forma_pago_retorna_agrupacion(): void
    {
        $formaPago = $this->getFormaPago();

        Pago::create([
            'empresa_id' => $this->empresa->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now(),
            'monto' => 10000,
            'estado' => 'pagado',
            'moneda' => 'CRC',
        ]);
        Pago::create([
            'empresa_id' => $this->empresa->id,
            'forma_pago_id' => $formaPago->id,
            'fecha_pago' => now(),
            'monto' => 20000,
            'estado' => 'pagado',
            'moneda' => 'CRC',
        ]);

        $resultado = $this->service->resumenPorFormaPago($this->empresa->id);

        $this->assertGreaterThanOrEqual(1, $resultado->count());
    }
}
