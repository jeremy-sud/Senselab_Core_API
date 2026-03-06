<?php

namespace Tests\Unit\Services;

use App\Models\CuentaPorPagar;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Services\CuentaPorPagarService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CuentaPorPagarServiceTest extends TestCase
{
    protected CuentaPorPagarService $service;
    protected Empresa $empresa;
    protected Proveedor $proveedor;
    protected Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPermisos();

        $this->service = new CuentaPorPagarService();
        $this->empresa = $this->createEmpresa();
        $this->usuario = $this->createAdminUsuario();
        $this->actingAs($this->usuario, 'sanctum');

        $this->proveedor = Proveedor::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '02',
            'numero_identificacion' => uniqid('PROV-'),
            'nombre' => 'Proveedor Test S.A.',
            'email' => 'proveedor@test.com',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function crearCuenta(array $override = []): CuentaPorPagar
    {
        return CuentaPorPagar::create(array_merge([
            'proveedor_id' => $this->proveedor->id,
            'numero_documento' => 'CXP-' . uniqid(),
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'moneda' => 'CRC',
            'monto_original' => 80000.00,
            'monto_pagado' => 0,
            'monto_pendiente' => 80000.00,
            'estado' => 'Pendiente',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $this->crearCuenta();
        $this->crearCuenta();

        $resultado = $this->service->listar($this->empresa->id);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_filtra_por_estado(): void
    {
        $this->crearCuenta(['estado' => 'Pendiente']);
        $this->crearCuenta(['estado' => 'Pagada']);

        $resultado = $this->service->listar($this->empresa->id, ['estado' => 'Pendiente']);

        foreach ($resultado->items() as $cuenta) {
            $this->assertEquals('Pendiente', $cuenta->estado);
        }
    }

    #[Test]
    public function listar_filtra_por_proveedor(): void
    {
        $this->crearCuenta();

        $otroProveedor = Proveedor::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => uniqid('P2-'),
            'nombre' => 'Otro Proveedor',
            'activo' => true,
            'eliminado' => false,
        ]);
        $this->crearCuenta(['proveedor_id' => $otroProveedor->id]);

        $resultado = $this->service->listar($this->empresa->id, ['proveedor_id' => $this->proveedor->id]);

        foreach ($resultado->items() as $cuenta) {
            $this->assertEquals($this->proveedor->id, $cuenta->proveedor_id);
        }
    }

    #[Test]
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

    #[Test]
    public function crear_cuenta_exitosamente(): void
    {
        $data = [
            'proveedor_id' => $this->proveedor->id,
            'numero_documento' => 'CXP-TEST-001',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'moneda' => 'CRC',
            'monto_original' => 100000.00,
            'monto_pagado' => 0,
            'monto_pendiente' => 100000.00,
            'estado' => 'Pendiente',
            'activo' => true,
            'eliminado' => false,
        ];

        $cuenta = $this->service->crear($data);

        $this->assertInstanceOf(CuentaPorPagar::class, $cuenta);
        $this->assertEquals('CXP-TEST-001', $cuenta->numero_documento);
    }

    #[Test]
    public function crear_establece_datos_correctamente(): void
    {
        $data = [
            'proveedor_id' => $this->proveedor->id,
            'numero_documento' => 'CXP-REC-001',
            'fecha_emision' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'moneda' => 'CRC',
            'monto_original' => 50000.00,
            'monto_pagado' => 0,
            'monto_pendiente' => 50000.00,
            'estado' => 'Pendiente',
            'activo' => true,
            'eliminado' => false,
        ];

        $cuenta = $this->service->crear($data);

        $this->assertEquals(50000.00, (float) $cuenta->monto_original);
        $this->assertDatabaseHas('cuentas_por_pagar', ['numero_documento' => 'CXP-REC-001']);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_cuenta_existente(): void
    {
        $cuenta = $this->crearCuenta();

        $resultado = $this->service->obtener($this->empresa->id, $cuenta->id);

        $this->assertEquals($cuenta->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('proveedor'));
    }

    #[Test]
    public function obtener_cuenta_inexistente_lanza_excepcion(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->obtener($this->empresa->id, 99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_cuenta_exitosamente(): void
    {
        $cuenta = $this->crearCuenta();

        $resultado = $this->service->actualizar($cuenta, [
            'monto_pagado' => 40000.00,
            'monto_pendiente' => 40000.00,
            'estado' => 'Pagada Parcialmente',
        ]);

        $this->assertEquals('Pagada Parcialmente', $resultado->estado);
        $this->assertEquals(40000.00, (float) $resultado->monto_pagado);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_cuenta_sin_pagos(): void
    {
        $cuenta = $this->crearCuenta(['monto_pagado' => 0]);

        $resultado = $this->service->eliminar($cuenta);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('cuentas_por_pagar', [
            'id' => $cuenta->id,
            'eliminado' => 1,
        ]);
    }

    #[Test]
    public function eliminar_cuenta_con_pagos_lanza_excepcion(): void
    {
        $cuenta = $this->crearCuenta(['monto_pagado' => 15000]);

        $this->expectException(ValidationException::class);

        $this->service->eliminar($cuenta);
    }

    // ─── vencidas() ─────────────────────────────────────────────

    #[Test]
    public function vencidas_retorna_resumen(): void
    {
        $this->crearCuenta([
            'fecha_vencimiento' => now()->subDays(10)->toDateString(),
            'monto_pendiente' => 50000.00,
        ]);

        $resultado = $this->service->vencidas($this->empresa->id);

        $this->assertArrayHasKey('total_vencido', $resultado);
        $this->assertArrayHasKey('cantidad_vencidas', $resultado);
        $this->assertArrayHasKey('cuentas', $resultado);
        $this->assertGreaterThanOrEqual(1, $resultado['cantidad_vencidas']);
    }

    // ─── resumen() ──────────────────────────────────────────────

    #[Test]
    public function resumen_retorna_datos_agrupados(): void
    {
        $this->crearCuenta();
        $this->crearCuenta(['monto_pagado' => 80000, 'monto_pendiente' => 0]);

        $resultado = $this->service->resumen($this->empresa->id);

        $this->assertArrayHasKey('por_estado', $resultado);
        $this->assertArrayHasKey('total_pendiente', $resultado);
    }
}
