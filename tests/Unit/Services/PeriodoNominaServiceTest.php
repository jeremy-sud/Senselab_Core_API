<?php

namespace Tests\Unit\Services;

use App\Models\Empresa;
use App\Models\PeriodoNomina;
use App\Services\PeriodoNominaService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PeriodoNominaServiceTest extends TestCase
{
    protected PeriodoNominaService $service;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPermisos();

        $this->service = new PeriodoNominaService();
        $this->empresa = $this->createEmpresa();
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function crearPeriodo(array $override = []): PeriodoNomina
    {
        return PeriodoNomina::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nombre_periodo' => 'Periodo ' . uniqid(),
            'fecha_inicio' => '2025-01-01',
            'fecha_fin' => '2025-01-15',
            'fecha_pago' => '2025-01-17',
            'estado' => 'Abierto',
            'total_salarios' => 0,
            'total_deducciones' => 0,
            'total_neto' => 0,
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    /** @test */
    public function listar_retorna_periodos_de_empresa(): void
    {
        $this->crearPeriodo(['fecha_inicio' => '2025-02-01', 'fecha_fin' => '2025-02-15']);
        $this->crearPeriodo(['fecha_inicio' => '2025-03-01', 'fecha_fin' => '2025-03-15']);

        $resultado = $this->service->listar($this->empresa->id);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    /** @test */
    public function listar_excluye_eliminados(): void
    {
        $this->crearPeriodo(['fecha_inicio' => '2025-04-01', 'fecha_fin' => '2025-04-15', 'eliminado' => false]);
        $this->crearPeriodo(['fecha_inicio' => '2025-05-01', 'fecha_fin' => '2025-05-15', 'eliminado' => true]);

        $resultado = $this->service->listar($this->empresa->id);

        foreach ($resultado->items() as $periodo) {
            $this->assertFalse((bool) $periodo->eliminado);
        }
    }

    /** @test */
    public function listar_filtra_por_estado(): void
    {
        $this->crearPeriodo(['estado' => 'Abierto', 'fecha_inicio' => '2025-06-01', 'fecha_fin' => '2025-06-15']);
        $this->crearPeriodo(['estado' => 'Cerrado', 'fecha_inicio' => '2025-07-01', 'fecha_fin' => '2025-07-15']);

        $resultado = $this->service->listar($this->empresa->id, ['estado' => 'Abierto']);

        foreach ($resultado->items() as $periodo) {
            $this->assertEquals('Abierto', $periodo->estado);
        }
    }

    /** @test */
    public function listar_filtra_por_anio(): void
    {
        $this->crearPeriodo(['fecha_inicio' => '2025-08-01', 'fecha_fin' => '2025-08-15']);
        $this->crearPeriodo(['fecha_inicio' => '2024-01-01', 'fecha_fin' => '2024-01-15']);

        $resultado = $this->service->listar($this->empresa->id, ['anio' => 2025]);

        foreach ($resultado->items() as $periodo) {
            $this->assertEquals(2025, $periodo->fecha_inicio->year);
        }
    }

    /** @test */
    public function listar_filtra_por_mes(): void
    {
        $this->crearPeriodo(['fecha_inicio' => '2025-09-01', 'fecha_fin' => '2025-09-15']);
        $this->crearPeriodo(['fecha_inicio' => '2025-10-01', 'fecha_fin' => '2025-10-15']);

        $resultado = $this->service->listar($this->empresa->id, ['mes' => 9]);

        foreach ($resultado->items() as $periodo) {
            $this->assertEquals(9, $periodo->fecha_inicio->month);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    /** @test */
    public function crear_periodo_exitosamente(): void
    {
        $data = [
            'nombre_periodo' => 'Enero 2025 Q1',
            'fecha_inicio' => '2025-11-01',
            'fecha_fin' => '2025-11-15',
            'fecha_pago' => '2025-11-17',
            'observaciones' => 'Primer período',
        ];

        $periodo = $this->service->crear($this->empresa->id, $data);

        $this->assertInstanceOf(PeriodoNomina::class, $periodo);
        $this->assertEquals($this->empresa->id, $periodo->empresa_id);
        $this->assertEquals('Abierto', $periodo->estado);
        $this->assertDatabaseHas('periodos_nomina', [
            'empresa_id' => $this->empresa->id,
            'nombre_periodo' => 'Enero 2025 Q1',
        ]);
    }

    /** @test */
    public function crear_periodo_estado_default_abierto(): void
    {
        $periodo = $this->service->crear($this->empresa->id, [
            'nombre_periodo' => 'Default Test',
            'fecha_inicio' => '2025-12-01',
            'fecha_fin' => '2025-12-15',
        ]);

        $this->assertEquals('Abierto', $periodo->estado);
    }

    // ─── obtener() ──────────────────────────────────────────────

    /** @test */
    public function obtener_periodo_existente(): void
    {
        $periodo = $this->crearPeriodo();

        $resultado = $this->service->obtener($this->empresa->id, $periodo->id);

        $this->assertEquals($periodo->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('empresa'));
    }

    /** @test */
    public function obtener_periodo_de_otra_empresa_falla(): void
    {
        $otraEmpresa = $this->createEmpresa(['nombre' => 'Otra', 'email' => 'otra' . uniqid() . '@test.com']);
        $periodo = $this->crearPeriodo();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->obtener($otraEmpresa->id, $periodo->id);
    }

    // ─── actualizar() ───────────────────────────────────────────

    /** @test */
    public function actualizar_periodo_abierto(): void
    {
        $periodo = $this->crearPeriodo(['estado' => 'Abierto']);

        $resultado = $this->service->actualizar($this->empresa->id, $periodo->id, [
            'observaciones' => 'Actualizado',
        ]);

        $this->assertEquals('Actualizado', $resultado->observaciones);
    }

    /** @test */
    public function actualizar_periodo_procesado_lanza_excepcion(): void
    {
        $periodo = $this->crearPeriodo(['estado' => 'Procesado']);

        $this->expectException(ValidationException::class);

        $this->service->actualizar($this->empresa->id, $periodo->id, [
            'observaciones' => 'No debería funcionar',
        ]);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    /** @test */
    public function eliminar_periodo_sin_pagos(): void
    {
        $periodo = $this->crearPeriodo();

        $resultado = $this->service->eliminar($this->empresa->id, $periodo->id);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('periodos_nomina', [
            'id' => $periodo->id,
            'eliminado' => true,
            'activo' => false,
        ]);
    }

    // ─── cerrar() ───────────────────────────────────────────────

    /** @test */
    public function cerrar_periodo_abierto(): void
    {
        $periodo = $this->crearPeriodo(['estado' => 'Abierto']);

        $resultado = $this->service->cerrar($this->empresa->id, $periodo->id);

        $this->assertEquals('Cerrado', $resultado->estado);
        $this->assertDatabaseHas('periodos_nomina', ['id' => $periodo->id, 'estado' => 'Cerrado']);
    }

    /** @test */
    public function cerrar_periodo_no_abierto_lanza_excepcion(): void
    {
        $periodo = $this->crearPeriodo(['estado' => 'Cerrado']);

        $this->expectException(ValidationException::class);

        $this->service->cerrar($this->empresa->id, $periodo->id);
    }

    /** @test */
    public function cerrar_periodo_procesado_lanza_excepcion(): void
    {
        $periodo = $this->crearPeriodo(['estado' => 'Procesado']);

        $this->expectException(ValidationException::class);

        $this->service->cerrar($this->empresa->id, $periodo->id);
    }

    // ─── procesar() ─────────────────────────────────────────────

    /** @test */
    public function procesar_periodo_no_procesado(): void
    {
        $periodo = $this->crearPeriodo(['estado' => 'Cerrado']);

        $resultado = $this->service->procesar($this->empresa->id, $periodo->id);

        $this->assertEquals('Procesado', $resultado->estado);
    }

    /** @test */
    public function procesar_periodo_ya_procesado_lanza_excepcion(): void
    {
        $periodo = $this->crearPeriodo(['estado' => 'Procesado']);

        $this->expectException(ValidationException::class);

        $this->service->procesar($this->empresa->id, $periodo->id);
    }

    // ─── resumen() ──────────────────────────────────────────────

    /** @test */
    public function resumen_retorna_estructura_correcta(): void
    {
        $periodo = $this->crearPeriodo();

        $resumen = $this->service->resumen($this->empresa->id, $periodo->id);

        $this->assertArrayHasKey('periodo', $resumen);
        $this->assertArrayHasKey('resumen', $resumen);
        $this->assertArrayHasKey('total_empleados', $resumen['resumen']);
        $this->assertArrayHasKey('total_bruto', $resumen['resumen']);
        $this->assertArrayHasKey('total_deducciones', $resumen['resumen']);
        $this->assertArrayHasKey('total_neto', $resumen['resumen']);
    }

    /** @test */
    public function resumen_sin_pagos_retorna_ceros(): void
    {
        $periodo = $this->crearPeriodo();

        $resumen = $this->service->resumen($this->empresa->id, $periodo->id);

        $this->assertEquals(0, $resumen['resumen']['total_empleados']);
        $this->assertEquals('0.00', $resumen['resumen']['total_bruto']);
    }

    // ─── activos() ──────────────────────────────────────────────

    /** @test */
    public function activos_retorna_periodos_activos_no_eliminados(): void
    {
        $this->crearPeriodo(['activo' => true, 'eliminado' => false, 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-01-15']);
        $this->crearPeriodo(['activo' => false, 'eliminado' => false, 'fecha_inicio' => '2026-02-01', 'fecha_fin' => '2026-02-15']);
        $this->crearPeriodo(['activo' => true, 'eliminado' => true, 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-15']);

        $activos = $this->service->activos($this->empresa->id);

        // Solo debe retornar el periodo activo y no eliminado
        $this->assertCount(1, $activos);
        $this->assertEquals('2026-01-01', $activos->first()->fecha_inicio->toDateString());
    }
}
