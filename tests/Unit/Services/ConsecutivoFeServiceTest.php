<?php

namespace Tests\Unit\Services;

use App\Models\ConsecutivoFe;
use App\Services\ConsecutivoFeService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConsecutivoFeServiceTest extends TestCase
{
    protected ConsecutivoFeService $service;
    private \App\Models\Empresa $empresa;
    private \App\Models\Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConsecutivoFeService();
        $this->empresa = $this->createEmpresa();
        $this->sucursal = $this->createSucursal($this->empresa);
    }

    private function crearConsecutivo(array $override = []): ConsecutivoFe
    {
        return ConsecutivoFe::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'sucursal_id' => $this->sucursal->id,
            'tipo_comprobante' => '01',
            'prefijo' => 'A',
            'consecutivo_actual' => 1,
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $this->crearConsecutivo();
        $this->crearConsecutivo(['tipo_comprobante' => '02', 'prefijo' => 'B']);

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id], 10);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_excluye_eliminados(): void
    {
        $this->crearConsecutivo();
        $this->crearConsecutivo(['tipo_comprobante' => '03', 'prefijo' => 'C', 'eliminado' => true]);

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id]);

        foreach ($resultado->items() as $item) {
            $this->assertFalse((bool) $item->eliminado);
        }
    }

    #[Test]
    public function listar_filtra_por_empresa_id(): void
    {
        $this->crearConsecutivo();

        $otraEmpresa = $this->createEmpresa(['email' => uniqid() . '@empresa.com']);
        $otraSucursal = $this->createSucursal($otraEmpresa);
        ConsecutivoFe::create([
            'empresa_id' => $otraEmpresa->id,
            'sucursal_id' => $otraSucursal->id,
            'tipo_comprobante' => '01',
            'prefijo' => 'X',
            'consecutivo_actual' => 1,
            'activo' => true,
            'eliminado' => false,
        ]);

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id]);

        foreach ($resultado->items() as $item) {
            $this->assertEquals($this->empresa->id, $item->empresa_id);
        }
    }

    #[Test]
    public function listar_filtra_por_sucursal_id(): void
    {
        $this->crearConsecutivo();
        $otraSucursal = $this->createSucursal($this->empresa);
        $this->crearConsecutivo(['sucursal_id' => $otraSucursal->id, 'tipo_comprobante' => '02', 'prefijo' => 'B']);

        $resultado = $this->service->listar([
            'empresa_id' => $this->empresa->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        foreach ($resultado->items() as $item) {
            $this->assertEquals($this->sucursal->id, $item->sucursal_id);
        }
    }

    #[Test]
    public function listar_filtra_por_estado(): void
    {
        $this->crearConsecutivo();

        $resultado = $this->service->listar([
            'empresa_id' => $this->empresa->id,
            'estado' => 'Activo',
        ]);

        $this->assertGreaterThanOrEqual(0, $resultado->total());
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_consecutivo(): void
    {
        $consecutivo = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'sucursal_id' => $this->sucursal->id,
            'tipo_comprobante' => '04',
            'prefijo' => 'D',
            'consecutivo_actual' => 1,
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->assertDatabaseHas('consecutivos_fe', [
            'id' => $consecutivo->id,
            'tipo_comprobante' => '04',
        ]);
    }

    // ─── actualizar() ──────────────────────────────────────────

    #[Test]
    public function actualizar_excluye_consecutivo_actual(): void
    {
        $consecutivo = $this->crearConsecutivo(['consecutivo_actual' => 100]);

        $this->service->actualizar($consecutivo, [
            'prefijo' => 'Z',
            'consecutivo_actual' => 999,
        ]);

        $fresh = $consecutivo->fresh();
        $this->assertEquals('Z', $fresh->prefijo);
        $this->assertEquals(100, $fresh->consecutivo_actual);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_soft_delete(): void
    {
        $consecutivo = $this->crearConsecutivo();

        $this->service->eliminar($consecutivo);

        $deleted = ConsecutivoFe::withoutGlobalScopes()->find($consecutivo->id);
        $this->assertFalse((bool) $deleted->activo);
        $this->assertTrue((bool) $deleted->eliminado);
    }

    // ─── resetear() ─────────────────────────────────────────────

    #[Test]
    public function resetear_cambia_consecutivo(): void
    {
        $consecutivo = $this->crearConsecutivo(['consecutivo_actual' => 100]);

        $resultado = $this->service->resetear($consecutivo, 500);

        $this->assertEquals(500, $resultado->fresh()->consecutivo_actual);
    }

    // ─── activar() ──────────────────────────────────────────────

    #[Test]
    public function activar_cambia_activo(): void
    {
        $consecutivo = $this->crearConsecutivo(['activo' => false]);

        $resultado = $this->service->activar($consecutivo);

        $this->assertTrue((bool) $resultado->fresh()->activo);
    }

    // ─── resumenPorEstado() ─────────────────────────────────────

    #[Test]
    public function resumenPorEstado_agrupa_correctamente(): void
    {
        $this->crearConsecutivo();
        $this->crearConsecutivo(['tipo_comprobante' => '02', 'prefijo' => 'B']);

        $resumen = $this->service->resumenPorEstado($this->empresa->id);

        $this->assertGreaterThanOrEqual(1, $resumen->count());
    }
}
