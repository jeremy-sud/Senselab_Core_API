<?php

namespace Tests\Unit\Services;

use App\Models\TasaImpuesto;
use App\Models\TipoImpuesto;
use App\Services\TasaImpuestoService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TasaImpuestoServiceTest extends TestCase
{
    protected TasaImpuestoService $service;
    protected TipoImpuesto $tipoImpuesto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TasaImpuestoService();
        $this->tipoImpuesto = TipoImpuesto::create([
            'codigo_hacienda' => 'TI' . strtoupper(substr(uniqid(), -4)),
            'nombre' => 'IVA Test ' . uniqid(),
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    private function crearTasa(array $override = []): TasaImpuesto
    {
        return TasaImpuesto::create(array_merge([
            'tipo_impuesto_id' => $this->tipoImpuesto->id,
            'tasa_porcentaje' => 13.00,
            'fecha_inicio_vigencia' => '2025-01-01',
            'fecha_fin_vigencia' => null,
            'descripcion' => 'Tasa test',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $this->crearTasa();
        $this->crearTasa(['tasa_porcentaje' => 8.00]);

        $resultado = $this->service->listar([], 10);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_excluye_eliminados(): void
    {
        $this->crearTasa(['eliminado' => false]);
        $this->crearTasa(['eliminado' => true]);

        $resultado = $this->service->listar();

        foreach ($resultado->items() as $tasa) {
            $this->assertFalse((bool) $tasa->eliminado);
        }
    }

    #[Test]
    public function listar_filtra_por_tipo_impuesto_id(): void
    {
        $otroTipo = TipoImpuesto::create([
            'codigo_hacienda' => 'OT' . substr(uniqid(), -4),
            'nombre' => 'Otro Tipo ' . uniqid(),
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->crearTasa();
        $this->crearTasa(['tipo_impuesto_id' => $otroTipo->id]);

        $resultado = $this->service->listar(['tipo_impuesto_id' => $this->tipoImpuesto->id]);

        foreach ($resultado->items() as $tasa) {
            $this->assertEquals($this->tipoImpuesto->id, $tasa->tipo_impuesto_id);
        }
    }

    #[Test]
    public function listar_filtra_vigentes(): void
    {
        $this->crearTasa([
            'fecha_inicio_vigencia' => Carbon::now()->subMonth(),
            'fecha_fin_vigencia' => null,
        ]);
        $this->crearTasa([
            'fecha_inicio_vigencia' => Carbon::now()->subYear(),
            'fecha_fin_vigencia' => Carbon::now()->subMonth(),
        ]);

        $resultado = $this->service->listar(['vigentes' => true]);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
    }

    #[Test]
    public function listar_carga_relacion_tipo_impuesto(): void
    {
        $this->crearTasa();

        $resultado = $this->service->listar();

        $this->assertTrue($resultado->items()[0]->relationLoaded('tipoImpuesto'));
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_tasa_exitosamente(): void
    {
        $data = [
            'tipo_impuesto_id' => $this->tipoImpuesto->id,
            'tasa_porcentaje' => 13.00,
            'fecha_inicio_vigencia' => '2025-06-01',
            'descripcion' => 'Tasa nueva',
            'activo' => true,
        ];

        $tasa = $this->service->crear($data);

        $this->assertInstanceOf(TasaImpuesto::class, $tasa);
        $this->assertEquals(13.00, $tasa->tasa_porcentaje);
        $this->assertTrue($tasa->relationLoaded('tipoImpuesto'));
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_tasa_existente(): void
    {
        $tasa = $this->crearTasa();

        $resultado = $this->service->obtener($tasa->id);

        $this->assertEquals($tasa->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('tipoImpuesto'));
    }

    #[Test]
    public function obtener_tasa_eliminada_lanza_excepcion(): void
    {
        $tasa = $this->crearTasa(['eliminado' => true]);

        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener($tasa->id);
    }

    #[Test]
    public function obtener_tasa_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_tasa_exitosamente(): void
    {
        $tasa = $this->crearTasa();

        $resultado = $this->service->actualizar($tasa, ['tasa_porcentaje' => 15.00]);

        $this->assertEquals(15.00, $resultado->tasa_porcentaje);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_tasa_marca_inactivo_y_eliminado(): void
    {
        $tasa = $this->crearTasa();

        $resultado = $this->service->eliminar($tasa);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('tasas_impuesto', [
            'id' => $tasa->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }

    // ─── vigente() ──────────────────────────────────────────────

    #[Test]
    public function vigente_retorna_tasa_actual(): void
    {
        $this->crearTasa([
            'fecha_inicio_vigencia' => Carbon::now()->subMonth(),
            'fecha_fin_vigencia' => null,
            'activo' => true,
        ]);

        $resultado = $this->service->vigente($this->tipoImpuesto->id);

        $this->assertNotNull($resultado);
        $this->assertEquals($this->tipoImpuesto->id, $resultado->tipo_impuesto_id);
        $this->assertTrue($resultado->relationLoaded('tipoImpuesto'));
    }

    #[Test]
    public function vigente_retorna_null_si_no_hay_tasa(): void
    {
        $resultado = $this->service->vigente($this->tipoImpuesto->id);

        $this->assertNull($resultado);
    }

    #[Test]
    public function vigente_respeta_fecha_fin(): void
    {
        $this->crearTasa([
            'fecha_inicio_vigencia' => Carbon::now()->subYear(),
            'fecha_fin_vigencia' => Carbon::now()->subMonth(),
            'activo' => true,
        ]);

        // No debe encontrar tasa vigente hoy porque expiró hace un mes
        $resultado = $this->service->vigente($this->tipoImpuesto->id);

        $this->assertNull($resultado);
    }

    #[Test]
    public function vigente_acepta_fecha_especifica(): void
    {
        $this->crearTasa([
            'fecha_inicio_vigencia' => '2024-01-01',
            'fecha_fin_vigencia' => '2024-12-31',
            'activo' => true,
        ]);

        $resultado = $this->service->vigente(
            $this->tipoImpuesto->id,
            Carbon::parse('2024-06-15')
        );

        $this->assertNotNull($resultado);
    }

    #[Test]
    public function vigente_excluye_eliminados(): void
    {
        $this->crearTasa([
            'fecha_inicio_vigencia' => Carbon::now()->subMonth(),
            'fecha_fin_vigencia' => null,
            'activo' => true,
            'eliminado' => true,
        ]);

        $resultado = $this->service->vigente($this->tipoImpuesto->id);

        $this->assertNull($resultado);
    }

    // ─── vigentesActuales() ─────────────────────────────────────

    #[Test]
    public function vigentes_actuales_retorna_tasas_vigentes(): void
    {
        $this->crearTasa([
            'fecha_inicio_vigencia' => Carbon::now()->subMonth(),
            'fecha_fin_vigencia' => null,
            'activo' => true,
        ]);
        // Tasa expirada — no debe aparecer
        $this->crearTasa([
            'fecha_inicio_vigencia' => Carbon::now()->subYear(),
            'fecha_fin_vigencia' => Carbon::now()->subMonth(),
            'activo' => true,
        ]);

        $resultado = $this->service->vigentesActuales();

        $this->assertGreaterThanOrEqual(1, $resultado->count());
        foreach ($resultado as $tasa) {
            $this->assertTrue($tasa->relationLoaded('tipoImpuesto'));
        }
    }

    // ─── historico() ────────────────────────────────────────────

    #[Test]
    public function historico_retorna_todas_las_tasas_del_tipo(): void
    {
        $this->crearTasa(['tasa_porcentaje' => 13.00, 'fecha_inicio_vigencia' => '2024-01-01']);
        $this->crearTasa(['tasa_porcentaje' => 10.00, 'fecha_inicio_vigencia' => '2023-01-01']);

        $resultado = $this->service->historico($this->tipoImpuesto->id);

        $this->assertCount(2, $resultado);
        // Ordenado descendente por fecha
        $this->assertTrue(
            $resultado->first()->fecha_inicio_vigencia >= $resultado->last()->fecha_inicio_vigencia
        );
    }

    #[Test]
    public function historico_excluye_eliminados(): void
    {
        $this->crearTasa(['eliminado' => false]);
        $this->crearTasa(['eliminado' => true]);

        $resultado = $this->service->historico($this->tipoImpuesto->id);

        $this->assertCount(1, $resultado);
    }

    #[Test]
    public function historico_no_incluye_otros_tipos(): void
    {
        $otroTipo = TipoImpuesto::create([
            'codigo_hacienda' => 'HI' . substr(uniqid(), -4),
            'nombre' => 'Otro ' . uniqid(),
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->crearTasa();
        TasaImpuesto::create([
            'tipo_impuesto_id' => $otroTipo->id,
            'tasa_porcentaje' => 5.00,
            'fecha_inicio_vigencia' => '2025-01-01',
            'activo' => true,
            'eliminado' => false,
        ]);

        $resultado = $this->service->historico($this->tipoImpuesto->id);

        foreach ($resultado as $tasa) {
            $this->assertEquals($this->tipoImpuesto->id, $tasa->tipo_impuesto_id);
        }
    }
}
