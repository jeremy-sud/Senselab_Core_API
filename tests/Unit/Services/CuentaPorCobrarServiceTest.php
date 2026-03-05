<?php

namespace Tests\Unit\Services;

use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Services\CuentaPorCobrarService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CuentaPorCobrarServiceTest extends TestCase
{
    protected CuentaPorCobrarService $service;
    protected Empresa $empresa;
    protected Cliente $cliente;
    protected Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPermisos();

        $this->service = new CuentaPorCobrarService();
        $this->empresa = $this->createEmpresa();
        $this->usuario = $this->createAdminUsuario();
        $this->actingAs($this->usuario, 'sanctum');

        $this->cliente = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => uniqid('CLI-'),
            'nombre' => 'Cliente Test',
            'apellidos' => 'Apellido Test',
            'email' => 'cliente@test.com',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function crearCuenta(array $override = []): CuentaPorCobrar
    {
        return CuentaPorCobrar::create(array_merge([
            'cliente_id' => $this->cliente->id,
            'numero_documento' => 'CXC-' . uniqid(),
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'moneda' => 'CRC',
            'monto_original' => 50000.00,
            'monto_pagado' => 0,
            'monto_pendiente' => 50000.00,
            'estado' => 'Pendiente',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    /** @test */
    public function listar_retorna_paginacion(): void
    {
        $this->crearCuenta();
        $this->crearCuenta();

        $resultado = $this->service->listar($this->empresa->id);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    /** @test */
    public function listar_filtra_por_estado(): void
    {
        $this->crearCuenta(['estado' => 'Pendiente']);
        $this->crearCuenta(['estado' => 'Pagada']);

        $resultado = $this->service->listar($this->empresa->id, ['estado' => 'Pendiente']);

        foreach ($resultado->items() as $cuenta) {
            $this->assertEquals('Pendiente', $cuenta->estado);
        }
    }

    /** @test */
    public function listar_filtra_por_cliente(): void
    {
        $this->crearCuenta();

        $otroCliente = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '02',
            'numero_identificacion' => uniqid('CLI2-'),
            'nombre' => 'Otro Cliente',
            'apellidos' => 'Test',
            'activo' => true,
            'eliminado' => false,
        ]);
        $this->crearCuenta(['cliente_id' => $otroCliente->id]);

        $resultado = $this->service->listar($this->empresa->id, ['cliente_id' => $this->cliente->id]);

        foreach ($resultado->items() as $cuenta) {
            $this->assertEquals($this->cliente->id, $cuenta->cliente_id);
        }
    }

    /** @test */
    public function listar_filtra_vencidas(): void
    {
        $this->crearCuenta([
            'fecha_vencimiento' => now()->subDays(5)->toDateString(),
        ]);
        $this->crearCuenta([
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
        ]);

        $resultado = $this->service->listar($this->empresa->id, ['vencidas' => true]);

        foreach ($resultado->items() as $cuenta) {
            $this->assertEquals('Vencida', $cuenta->estado);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    /** @test */
    public function crear_cuenta_exitosamente(): void
    {
        $data = [
            'cliente_id' => $this->cliente->id,
            'numero_documento' => 'CXC-TEST-001',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'moneda' => 'CRC',
            'monto_original' => 75000.00,
            'monto_pagado' => 0,
            'monto_pendiente' => 75000.00,
            'estado' => 'Pendiente',
            'activo' => true,
            'eliminado' => false,
        ];

        $cuenta = $this->service->crear($data);

        $this->assertInstanceOf(CuentaPorCobrar::class, $cuenta);
        $this->assertEquals('CXC-TEST-001', $cuenta->numero_documento);
        $this->assertEquals(75000.00, (float) $cuenta->monto_original);
        $this->assertDatabaseHas('cuentas_por_cobrar', ['numero_documento' => 'CXC-TEST-001']);
    }

    // ─── obtener() ──────────────────────────────────────────────

    /** @test */
    public function obtener_cuenta_existente(): void
    {
        $cuenta = $this->crearCuenta();

        $resultado = $this->service->obtener($this->empresa->id, $cuenta->id);

        $this->assertEquals($cuenta->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('cliente'));
    }

    /** @test */
    public function obtener_cuenta_inexistente_lanza_excepcion(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->obtener($this->empresa->id, 99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    /** @test */
    public function actualizar_cuenta_exitosamente(): void
    {
        $cuenta = $this->crearCuenta();

        $resultado = $this->service->actualizar($cuenta, [
            'monto_pagado' => 25000.00,
            'monto_pendiente' => 25000.00,
            'estado' => 'Pagada Parcialmente',
        ]);

        $this->assertEquals('Pagada Parcialmente', $resultado->estado);
        $this->assertEquals(25000.00, (float) $resultado->monto_pagado);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    /** @test */
    public function eliminar_cuenta_sin_pagos(): void
    {
        $cuenta = $this->crearCuenta(['monto_pagado' => 0]);

        $resultado = $this->service->eliminar($cuenta);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('cuentas_por_cobrar', [
            'id' => $cuenta->id,
            'eliminado' => 1,
        ]);
    }

    /** @test */
    public function eliminar_cuenta_con_pagos_lanza_excepcion(): void
    {
        $cuenta = $this->crearCuenta(['monto_pagado' => 10000]);

        $this->expectException(ValidationException::class);

        $this->service->eliminar($cuenta);
    }

    // ─── vencidas() ─────────────────────────────────────────────

    /** @test */
    public function vencidas_retorna_resumen(): void
    {
        $this->crearCuenta([
            'fecha_vencimiento' => now()->subDays(10)->toDateString(),
            'monto_pendiente' => 30000.00,
        ]);
        $this->crearCuenta([
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
        ]);

        $resultado = $this->service->vencidas($this->empresa->id);

        $this->assertArrayHasKey('total_vencido', $resultado);
        $this->assertArrayHasKey('cantidad_vencidas', $resultado);
        $this->assertArrayHasKey('cuentas', $resultado);
        $this->assertGreaterThanOrEqual(1, $resultado['cantidad_vencidas']);
    }

    // ─── resumen() ──────────────────────────────────────────────

    /** @test */
    public function resumen_retorna_datos_agrupados(): void
    {
        $this->crearCuenta();
        $this->crearCuenta(['monto_pagado' => 50000, 'monto_pendiente' => 0]);

        $resultado = $this->service->resumen($this->empresa->id);

        $this->assertArrayHasKey('por_estado', $resultado);
        $this->assertArrayHasKey('total_pendiente', $resultado);
    }
}
