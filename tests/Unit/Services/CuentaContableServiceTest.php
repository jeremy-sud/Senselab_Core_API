<?php

namespace Tests\Unit\Services;

use App\Models\CuentaContable;
use App\Models\Empresa;
use App\Models\TipoCuenta;
use App\Services\CuentaContableService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CuentaContableServiceTest extends TestCase
{
    protected CuentaContableService $service;
    protected Empresa $empresa;
    protected TipoCuenta $tipoCuenta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPermisos();

        $this->service = new CuentaContableService();
        $this->empresa = $this->createEmpresa();
        $this->tipoCuenta = TipoCuenta::create([
            'nombre' => 'Activo',
            'descripcion' => 'Cuentas de activo',
            'naturaleza' => 'Deudora',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function crearCuenta(array $override = []): CuentaContable
    {
        return CuentaContable::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'tipo_cuenta_id' => $this->tipoCuenta->id,
            'codigo' => uniqid('CC-'),
            'nombre' => 'Cuenta Test ' . uniqid(),
            'descripcion' => 'Cuenta de prueba',
            'cuenta_padre_id' => null,
            'permite_movimientos' => true,
            'saldo_actual' => 0.00,
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    /** @test */
    public function listar_retorna_cuentas_de_empresa(): void
    {
        $this->crearCuenta();
        $this->crearCuenta();

        $resultado = $this->service->listar($this->empresa->id);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    /** @test */
    public function listar_excluye_eliminadas(): void
    {
        $this->crearCuenta(['eliminado' => false]);
        $this->crearCuenta(['eliminado' => true]);

        $resultado = $this->service->listar($this->empresa->id);

        foreach ($resultado->items() as $cuenta) {
            $this->assertFalse((bool) $cuenta->eliminado);
        }
    }

    /** @test */
    public function listar_filtra_por_tipo_cuenta(): void
    {
        $otroTipo = TipoCuenta::create([
            'nombre' => 'Pasivo',
            'descripcion' => 'Cuentas de pasivo',
            'naturaleza' => 'Acreedora',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->crearCuenta(['tipo_cuenta_id' => $this->tipoCuenta->id]);
        $this->crearCuenta(['tipo_cuenta_id' => $otroTipo->id]);

        $resultado = $this->service->listar($this->empresa->id, ['tipo_cuenta_id' => $this->tipoCuenta->id]);

        foreach ($resultado->items() as $cuenta) {
            $this->assertEquals($this->tipoCuenta->id, $cuenta->tipo_cuenta_id);
        }
    }

    /** @test */
    public function listar_filtra_principales_sin_padre(): void
    {
        $padre = $this->crearCuenta(['cuenta_padre_id' => null]);
        $this->crearCuenta(['cuenta_padre_id' => $padre->id]);

        $resultado = $this->service->listar($this->empresa->id, ['principales' => 1]);

        foreach ($resultado->items() as $cuenta) {
            $this->assertNull($cuenta->cuenta_padre_id);
        }
    }

    /** @test */
    public function listar_filtra_por_codigo(): void
    {
        $this->crearCuenta(['codigo' => '1000-01']);
        $this->crearCuenta(['codigo' => '2000-01']);

        $resultado = $this->service->listar($this->empresa->id, ['codigo' => '1000']);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
        foreach ($resultado->items() as $cuenta) {
            $this->assertStringContainsString('1000', $cuenta->codigo);
        }
    }

    /** @test */
    public function listar_filtra_por_permite_movimientos(): void
    {
        $this->crearCuenta(['permite_movimientos' => true]);
        $this->crearCuenta(['permite_movimientos' => false]);

        $resultado = $this->service->listar($this->empresa->id, ['permite_movimientos' => true]);

        foreach ($resultado->items() as $cuenta) {
            $this->assertTrue((bool) $cuenta->permite_movimientos);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    /** @test */
    public function crear_cuenta_contable(): void
    {
        $data = [
            'tipo_cuenta_id' => $this->tipoCuenta->id,
            'codigo' => '1000-00-00',
            'nombre' => 'Caja General',
            'permite_movimientos' => true,
        ];

        $cuenta = $this->service->crear($this->empresa->id, $data);

        $this->assertInstanceOf(CuentaContable::class, $cuenta);
        $this->assertEquals($this->empresa->id, $cuenta->empresa_id);
        $this->assertEquals('Caja General', $cuenta->nombre);
        $this->assertDatabaseHas('cuentas_contables', [
            'empresa_id' => $this->empresa->id,
            'codigo' => '1000-00-00',
        ]);
    }

    /** @test */
    public function crear_cuenta_carga_relaciones(): void
    {
        $cuenta = $this->service->crear($this->empresa->id, [
            'tipo_cuenta_id' => $this->tipoCuenta->id,
            'codigo' => '1001',
            'nombre' => 'Con Relaciones',
        ]);

        $this->assertTrue($cuenta->relationLoaded('tipoCuenta'));
        $this->assertTrue($cuenta->relationLoaded('subcuentas'));
    }

    // ─── obtener() ──────────────────────────────────────────────

    /** @test */
    public function obtener_cuenta_existente(): void
    {
        $cuenta = $this->crearCuenta();

        $resultado = $this->service->obtener($this->empresa->id, $cuenta->id);

        $this->assertEquals($cuenta->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('tipoCuenta'));
    }

    /** @test */
    public function obtener_cuenta_de_otra_empresa_falla(): void
    {
        $otraEmpresa = $this->createEmpresa(['nombre' => 'Otra', 'email' => 'otra' . uniqid() . '@test.com']);
        $cuenta = $this->crearCuenta();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->obtener($otraEmpresa->id, $cuenta->id);
    }

    /** @test */
    public function obtener_cuenta_eliminada_falla(): void
    {
        $cuenta = $this->crearCuenta(['eliminado' => true]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->obtener($this->empresa->id, $cuenta->id);
    }

    // ─── actualizar() ───────────────────────────────────────────

    /** @test */
    public function actualizar_cuenta_contable(): void
    {
        $cuenta = $this->crearCuenta(['nombre' => 'Nombre Viejo']);

        $resultado = $this->service->actualizar($this->empresa->id, $cuenta->id, ['nombre' => 'Nombre Nuevo']);

        $this->assertEquals('Nombre Nuevo', $resultado->nombre);
        $this->assertDatabaseHas('cuentas_contables', ['id' => $cuenta->id, 'nombre' => 'Nombre Nuevo']);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    /** @test */
    public function eliminar_cuenta_sin_dependencias(): void
    {
        $cuenta = $this->crearCuenta();

        $resultado = $this->service->eliminar($this->empresa->id, $cuenta->id);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('cuentas_contables', [
            'id' => $cuenta->id,
            'eliminado' => true,
            'activo' => false,
        ]);
    }

    /** @test */
    public function eliminar_cuenta_con_subcuentas_lanza_excepcion(): void
    {
        $padre = $this->crearCuenta();
        $this->crearCuenta(['cuenta_padre_id' => $padre->id]); // subcuenta activa

        $this->expectException(ValidationException::class);

        $this->service->eliminar($this->empresa->id, $padre->id);
    }

    // ─── arbol() ────────────────────────────────────────────────

    /** @test */
    public function arbol_retorna_estructura_jerarquica(): void
    {
        $padre = $this->crearCuenta(['cuenta_padre_id' => null, 'codigo' => '1000']);
        $this->crearCuenta(['cuenta_padre_id' => $padre->id, 'codigo' => '1001']);

        $arbol = $this->service->arbol($this->empresa->id);

        $this->assertGreaterThanOrEqual(1, $arbol->count());
        $raiz = $arbol->firstWhere('id', $padre->id);
        $this->assertNotNull($raiz);
        $this->assertTrue($raiz->relationLoaded('subcuentas'));
        $this->assertGreaterThanOrEqual(1, $raiz->subcuentas->count());
    }

    // ─── paraMovimientos() ──────────────────────────────────────

    /** @test */
    public function para_movimientos_retorna_solo_cuentas_de_movimiento(): void
    {
        $this->crearCuenta(['permite_movimientos' => true, 'activo' => true]);
        $this->crearCuenta(['permite_movimientos' => false, 'activo' => true]);

        $cuentas = $this->service->paraMovimientos($this->empresa->id);

        foreach ($cuentas as $cuenta) {
            $this->assertTrue((bool) $cuenta->permite_movimientos);
            $this->assertTrue((bool) $cuenta->activo);
        }
    }
}
