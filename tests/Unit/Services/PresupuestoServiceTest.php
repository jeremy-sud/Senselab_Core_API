<?php

namespace Tests\Unit\Services;

use App\Models\CuentaContable;
use App\Models\DetallePresupuesto;
use App\Models\Empresa;
use App\Models\Presupuesto;
use App\Models\Usuario;
use App\Services\PresupuestoService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PresupuestoServiceTest extends TestCase
{
    protected PresupuestoService $service;
    protected Empresa $empresa;
    protected Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPermisos();

        $this->service = new PresupuestoService();
        $this->empresa = $this->createEmpresa();
        $this->usuario = $this->createAdminUsuario();
        $this->actingAs($this->usuario, 'sanctum');
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function crearPresupuesto(array $override = []): Presupuesto
    {
        return Presupuesto::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Presupuesto Test ' . uniqid(),
            'periodo_inicio' => now()->startOfMonth()->toDateString(),
            'periodo_fin' => now()->endOfMonth()->toDateString(),
            'estado' => 'Borrador',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    private function agregarDetalle(Presupuesto $presupuesto): DetallePresupuesto
    {
        $cuenta = CuentaContable::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Cuenta Test ' . uniqid(),
            'codigo' => 'CT-' . uniqid(),
            'permite_movimientos' => true,
            'activo' => true,
            'eliminado' => false,
        ]);

        return DetallePresupuesto::create([
            'presupuesto_id' => $presupuesto->id,
            'cuenta_contable_id' => $cuenta->id,
            'monto_presupuestado' => 500000.00,
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $this->crearPresupuesto();
        $this->crearPresupuesto();

        $resultado = $this->service->listar($this->empresa->id);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_presupuesto_exitosamente(): void
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Presupuesto Anual 2025',
            'periodo_inicio' => '2025-01-01',
            'periodo_fin' => '2025-12-31',
            'activo' => true,
            'eliminado' => false,
        ];

        $presupuesto = $this->service->crear($data);

        $this->assertInstanceOf(Presupuesto::class, $presupuesto);
        $this->assertEquals('Borrador', $presupuesto->estado);
        $this->assertEquals('Presupuesto Anual 2025', $presupuesto->nombre);
        $this->assertDatabaseHas('presupuestos', ['nombre' => 'Presupuesto Anual 2025']);
    }

    #[Test]
    public function crear_establece_estado_borrador_por_defecto(): void
    {
        $presupuesto = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Sin estado explícito',
            'periodo_inicio' => '2025-06-01',
            'periodo_fin' => '2025-06-30',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->assertEquals('Borrador', $presupuesto->estado);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_presupuesto_existente(): void
    {
        $presupuesto = $this->crearPresupuesto();

        $resultado = $this->service->obtener($this->empresa->id, $presupuesto->id);

        $this->assertEquals($presupuesto->id, $resultado->id);
    }

    #[Test]
    public function obtener_presupuesto_inexistente_lanza_excepcion(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->obtener($this->empresa->id, 99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_presupuesto_borrador(): void
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Borrador']);

        $resultado = $this->service->actualizar($presupuesto, [
            'nombre' => 'Nombre Actualizado',
        ]);

        $this->assertEquals('Nombre Actualizado', $resultado->nombre);
    }

    #[Test]
    public function actualizar_presupuesto_finalizado_lanza_excepcion(): void
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Finalizado']);

        $this->expectException(ValidationException::class);

        $this->service->actualizar($presupuesto, ['nombre' => 'No debería cambiar']);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_presupuesto_borrador(): void
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Borrador']);

        $resultado = $this->service->eliminar($presupuesto);

        $this->assertTrue($resultado);
    }

    #[Test]
    public function eliminar_presupuesto_activo_lanza_excepcion(): void
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Activo']);

        $this->expectException(ValidationException::class);

        $this->service->eliminar($presupuesto);
    }

    // ─── activar() ──────────────────────────────────────────────

    #[Test]
    public function activar_presupuesto_con_detalles(): void
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Borrador']);
        $this->agregarDetalle($presupuesto);
        $presupuesto->load('detalles');

        $resultado = $this->service->activar($presupuesto);

        $this->assertEquals('Activo', $resultado->estado);
    }

    #[Test]
    public function activar_presupuesto_sin_detalles_lanza_excepcion(): void
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Borrador']);
        $presupuesto->load('detalles');

        $this->expectException(ValidationException::class);

        $this->service->activar($presupuesto);
    }

    #[Test]
    public function activar_presupuesto_ya_activo_lanza_excepcion(): void
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Activo']);
        $this->agregarDetalle($presupuesto);
        $presupuesto->load('detalles');

        $this->expectException(ValidationException::class);

        $this->service->activar($presupuesto);
    }

    // ─── finalizar() ────────────────────────────────────────────

    #[Test]
    public function finalizar_presupuesto_activo(): void
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Activo']);

        $resultado = $this->service->finalizar($presupuesto);

        $this->assertEquals('Finalizado', $resultado->estado);
    }

    #[Test]
    public function finalizar_presupuesto_ya_finalizado_lanza_excepcion(): void
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Finalizado']);

        $this->expectException(ValidationException::class);

        $this->service->finalizar($presupuesto);
    }

    // ─── activos() ──────────────────────────────────────────────

    #[Test]
    public function activos_retorna_solo_activos(): void
    {
        $this->crearPresupuesto(['estado' => 'Activo']);
        $this->crearPresupuesto(['estado' => 'Borrador']);
        $this->crearPresupuesto(['estado' => 'Activo']);

        $resultado = $this->service->activos($this->empresa->id);

        $this->assertGreaterThanOrEqual(2, $resultado->count());
        foreach ($resultado as $presupuesto) {
            $this->assertEquals('Activo', $presupuesto->estado);
        }
    }

    // ─── resumen() ──────────────────────────────────────────────

    #[Test]
    public function resumen_retorna_datos_completos(): void
    {
        $presupuesto = $this->crearPresupuesto();
        $this->agregarDetalle($presupuesto);

        $resultado = $this->service->resumen($this->empresa->id, $presupuesto->id);

        $this->assertArrayHasKey('presupuesto', $resultado);
        $this->assertArrayHasKey('total_presupuestado', $resultado);
        $this->assertArrayHasKey('cantidad_lineas', $resultado);
        $this->assertArrayHasKey('duracion_dias', $resultado);
        $this->assertEquals(1, $resultado['cantidad_lineas']);
        $this->assertEquals(500000.00, (float) $resultado['total_presupuestado']);
    }
}
