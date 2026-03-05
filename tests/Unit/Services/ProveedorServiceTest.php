<?php

namespace Tests\Unit\Services;

use App\Models\Empresa;
use App\Models\Proveedor;
use App\Services\ProveedorService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class ProveedorServiceTest extends TestCase
{
    protected ProveedorService $service;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPermisos();

        $this->service = new ProveedorService();
        $this->empresa = $this->createEmpresa();
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function crearProveedor(array $override = []): Proveedor
    {
        return Proveedor::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '02',
            'numero_identificacion' => uniqid('PROV-'),
            'nombre' => 'Proveedor Test ' . uniqid(),
            'nombre_comercial' => 'Comercial Test',
            'email' => 'prov' . uniqid() . '@test.com',
            'telefono' => '2222-3333',
            'direccion' => 'San José',
            'limite_credito' => 500000.00,
            'plazo_credito_dias' => 30,
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    /** @test */
    public function listar_retorna_paginacion(): void
    {
        $this->crearProveedor();
        $this->crearProveedor();

        $resultado = $this->service->listar();

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    /** @test */
    public function listar_busca_por_nombre(): void
    {
        $this->crearProveedor(['nombre' => 'Distribuidora Nacional']);
        $this->crearProveedor(['nombre' => 'Importadora Global']);

        $resultado = $this->service->listar(['search' => 'Distribuidora']);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
        $this->assertStringContainsString('Distribuidora', $resultado->first()->nombre);
    }

    /** @test */
    public function listar_busca_por_numero_identificacion(): void
    {
        $this->crearProveedor(['numero_identificacion' => 'NIT-UNICO-456']);

        $resultado = $this->service->listar(['search' => 'NIT-UNICO-456']);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
    }

    /** @test */
    public function listar_busca_por_email(): void
    {
        $this->crearProveedor(['email' => 'unico_email@proveedor.com']);

        $resultado = $this->service->listar(['search' => 'unico_email@proveedor']);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
    }

    /** @test */
    public function listar_filtra_por_empresa(): void
    {
        $this->crearProveedor();
        $otraEmpresa = $this->createEmpresa(['nombre' => 'Otra', 'email' => 'otra' . uniqid() . '@test.com']);
        $this->crearProveedor(['empresa_id' => $otraEmpresa->id]);

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id]);

        foreach ($resultado->items() as $prov) {
            $this->assertEquals($this->empresa->id, $prov->empresa_id);
        }
    }

    /** @test */
    public function listar_filtra_solo_activos(): void
    {
        $this->crearProveedor(['activo' => true]);
        $this->crearProveedor(['activo' => false]);

        $resultado = $this->service->listar(['activos' => true]);

        foreach ($resultado->items() as $prov) {
            $this->assertTrue((bool) $prov->activo);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    /** @test */
    public function crear_proveedor_exitosamente(): void
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '1-1234-5678',
            'nombre' => 'Nuevo Proveedor S.A.',
            'email' => 'nuevo@proveedor.com',
            'activo' => true,
            'eliminado' => false,
        ];

        $proveedor = $this->service->crear($data);

        $this->assertInstanceOf(Proveedor::class, $proveedor);
        $this->assertEquals('Nuevo Proveedor S.A.', $proveedor->nombre);
        $this->assertTrue($proveedor->relationLoaded('empresa'));
        $this->assertDatabaseHas('proveedores', [
            'nombre' => 'Nuevo Proveedor S.A.',
            'numero_identificacion' => '1-1234-5678',
        ]);
    }

    // ─── obtener() ──────────────────────────────────────────────

    /** @test */
    public function obtener_proveedor_existente(): void
    {
        $proveedor = $this->crearProveedor();

        $resultado = $this->service->obtener($proveedor->id);

        $this->assertEquals($proveedor->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('empresa'));
        $this->assertTrue($resultado->relationLoaded('ordenesCompra'));
    }

    /** @test */
    public function obtener_proveedor_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    /** @test */
    public function actualizar_proveedor_exitosamente(): void
    {
        $proveedor = $this->crearProveedor(['telefono' => '1111-1111']);

        $resultado = $this->service->actualizar($proveedor, ['telefono' => '9999-9999']);

        $this->assertEquals('9999-9999', $resultado->telefono);
        $this->assertDatabaseHas('proveedores', ['id' => $proveedor->id, 'telefono' => '9999-9999']);
    }

    /** @test */
    public function actualizar_nombre_proveedor(): void
    {
        $proveedor = $this->crearProveedor(['nombre' => 'Viejo Nombre']);

        $resultado = $this->service->actualizar($proveedor, ['nombre' => 'Nuevo Nombre']);

        $this->assertEquals('Nuevo Nombre', $resultado->nombre);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    /** @test */
    public function eliminar_proveedor_soft_delete(): void
    {
        $proveedor = $this->crearProveedor();

        $resultado = $this->service->eliminar($proveedor);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }

    // ─── calcularSaldoPendiente() ───────────────────────────────

    /** @test */
    public function calcular_saldo_pendiente_sin_cuentas(): void
    {
        $proveedor = $this->crearProveedor();

        $saldo = $this->service->calcularSaldoPendiente($proveedor);

        $this->assertEquals(0.0, $saldo);
    }
}
