<?php

namespace Tests\Unit\Services;

use App\Models\OrdenCompra;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\Usuario;
use App\Services\OrdenCompraService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OrdenCompraServiceTest extends TestCase
{
    protected OrdenCompraService $service;
    protected Empresa $empresa;
    protected Proveedor $proveedor;
    protected Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPermisos();

        $this->service = new OrdenCompraService();
        $this->empresa = $this->createEmpresa();
        $this->usuario = $this->createAdminUsuario();

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

    private function crearOrden(array $override = []): OrdenCompra
    {
        return OrdenCompra::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'usuario_id' => $this->usuario->id,
            'numero_orden' => 'OC-' . str_pad((string) rand(1, 999999), 6, '0', STR_PAD_LEFT),
            'fecha_orden' => now()->toDateString(),
            'fecha_entrega_esperada' => now()->addDays(15)->toDateString(),
            'moneda' => 'CRC',
            'subtotal' => 100000.00,
            'impuesto_total' => 13000.00,
            'total_orden' => 113000.00,
            'estado' => 'borrador',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $this->crearOrden();
        $this->crearOrden();

        $resultado = $this->service->listar();

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_filtra_por_estado(): void
    {
        $this->crearOrden(['estado' => 'borrador']);
        $this->crearOrden(['estado' => 'enviada']);

        $resultado = $this->service->listar(['estado' => 'borrador']);

        foreach ($resultado->items() as $orden) {
            $this->assertEquals('borrador', $orden->estado);
        }
    }

    #[Test]
    public function listar_filtra_por_proveedor(): void
    {
        $this->crearOrden();

        $otroProveedor = Proveedor::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => uniqid('P2-'),
            'nombre' => 'Otro Proveedor',
            'activo' => true,
            'eliminado' => false,
        ]);
        $this->crearOrden(['proveedor_id' => $otroProveedor->id]);

        $resultado = $this->service->listar(['proveedor_id' => $this->proveedor->id]);

        foreach ($resultado->items() as $orden) {
            $this->assertEquals($this->proveedor->id, $orden->proveedor_id);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_orden_con_detalles(): void
    {
        // Crear producto necesario para el detalle
        $producto = $this->createProducto([], $this->empresa);

        $data = [
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'usuario_id' => $this->usuario->id,
            'fecha_orden' => now()->toDateString(),
            'fecha_entrega_esperada' => now()->addDays(10)->toDateString(),
            'moneda' => 'CRC',
            'estado' => 'borrador',
        ];

        $detalles = [
            [
                'producto_id' => $producto->id,
                'cantidad' => 10,
                'precio_unitario' => 5000.00,
                'detalle_adicional' => 'Producto test',
            ],
        ];

        $orden = $this->service->crear($data, $detalles);

        $this->assertInstanceOf(OrdenCompra::class, $orden);
        $this->assertStringStartsWith('OC-', $orden->numero_orden);
        $this->assertEquals(50000.00, (float) $orden->subtotal);
        $this->assertEquals(50000.00, (float) $orden->total_orden);
        $this->assertDatabaseHas('ordenes_compra', ['id' => $orden->id]);
    }

    #[Test]
    public function crear_genera_numero_orden_automatico(): void
    {
        $producto = $this->createProducto([], $this->empresa);

        $orden1 = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'usuario_id' => $this->usuario->id,
            'fecha_orden' => now()->toDateString(),
            'moneda' => 'CRC',
            'estado' => 'borrador',
        ], [[
            'producto_id' => $producto->id,
            'cantidad' => 1,
            'precio_unitario' => 1000,
        ]]);

        $orden2 = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'usuario_id' => $this->usuario->id,
            'fecha_orden' => now()->toDateString(),
            'moneda' => 'CRC',
            'estado' => 'borrador',
        ], [[
            'producto_id' => $producto->id,
            'cantidad' => 2,
            'precio_unitario' => 2000,
        ]]);

        $this->assertNotEquals($orden1->numero_orden, $orden2->numero_orden);
    }

    #[Test]
    public function crear_calcula_totales_correctamente(): void
    {
        $producto = $this->createProducto([], $this->empresa);

        $orden = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'usuario_id' => $this->usuario->id,
            'fecha_orden' => now()->toDateString(),
            'moneda' => 'CRC',
            'estado' => 'borrador',
        ], [
            [
                'producto_id' => $producto->id,
                'cantidad' => 5,
                'precio_unitario' => 10000.00,
            ],
            [
                'producto_id' => $producto->id,
                'cantidad' => 3,
                'precio_unitario' => 8000.00,
            ],
        ]);

        // (5*10000) + (3*8000) = 50000 + 24000 = 74000
        $this->assertEquals(74000.00, (float) $orden->subtotal);
        $this->assertEquals(74000.00, (float) $orden->total_orden);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_orden_existente(): void
    {
        $orden = $this->crearOrden();

        $resultado = $this->service->obtener($orden->id);

        $this->assertEquals($orden->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('proveedor'));
        $this->assertTrue($resultado->relationLoaded('empresa'));
    }

    #[Test]
    public function obtener_orden_inexistente_lanza_excepcion(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_orden(): void
    {
        $orden = $this->crearOrden(['observaciones' => null]);

        $resultado = $this->service->actualizar($orden, [
            'observaciones' => 'Nota de prueba',
        ]);

        $this->assertEquals('Nota de prueba', $resultado->observaciones);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_orden_borrador(): void
    {
        $orden = $this->crearOrden(['estado' => 'borrador']);

        $resultado = $this->service->eliminar($orden);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('ordenes_compra', [
            'id' => $orden->id,
            'eliminado' => true,
            'activo' => false,
        ]);
    }

    #[Test]
    public function eliminar_orden_no_borrador_lanza_excepcion(): void
    {
        $orden = $this->crearOrden(['estado' => 'enviada']);

        $this->expectException(ValidationException::class);

        $this->service->eliminar($orden);
    }

    #[Test]
    public function eliminar_orden_recibida_lanza_excepcion(): void
    {
        $orden = $this->crearOrden(['estado' => 'recibida']);

        $this->expectException(ValidationException::class);

        $this->service->eliminar($orden);
    }
}
