<?php

namespace Tests\Unit\Services;

use App\Exceptions\BusinessException;
use App\Models\Empresa;
use App\Models\HorarioRuta;
use App\Models\Ruta;
use App\Services\RutaService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RutaServiceTest extends TestCase
{
    protected RutaService $service;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RutaService();
        $this->empresa = $this->createEmpresa();
    }

    private function crearRuta(array $override = []): Ruta
    {
        return Ruta::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Ruta ' . uniqid(),
            'origen' => 'San José',
            'destino' => 'Limón',
            'distancia_km' => 160.5,
            'duracion_estimada' => 240,
            'tarifa_base' => 5000.00,
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listarTodos() ──────────────────────────────────────────

    #[Test]
    public function listar_todos_retorna_coleccion(): void
    {
        $this->crearRuta();
        $this->crearRuta();

        $resultado = $this->service->listarTodos(['empresa_id' => $this->empresa->id]);

        $this->assertGreaterThanOrEqual(2, $resultado->count());
    }

    #[Test]
    public function listar_todos_filtra_por_activo(): void
    {
        $this->crearRuta(['activo' => true]);
        $this->crearRuta(['activo' => false]);

        $resultado = $this->service->listarTodos([
            'empresa_id' => $this->empresa->id,
            'activo' => true,
        ]);

        foreach ($resultado as $ruta) {
            $this->assertTrue((bool) $ruta->activo);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_ruta_exitosamente(): void
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Ruta Nueva',
            'origen' => 'Alajuela',
            'destino' => 'Puntarenas',
            'distancia_km' => 100,
            'tarifa_base' => 3000,
            'activo' => true,
        ];

        $ruta = $this->service->crear($data);

        $this->assertInstanceOf(Ruta::class, $ruta);
        $this->assertDatabaseHas('rutas', ['id' => $ruta->id, 'nombre' => 'Ruta Nueva']);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_ruta_existente(): void
    {
        $ruta = $this->crearRuta();

        $resultado = $this->service->obtener($ruta->id);

        $this->assertEquals($ruta->id, $resultado->id);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_ruta_exitosamente(): void
    {
        $ruta = $this->crearRuta();

        $resultado = $this->service->actualizar($ruta, ['nombre' => 'Ruta Actualizada']);

        $this->assertEquals('Ruta Actualizada', $resultado->nombre);
        $this->assertDatabaseHas('rutas', ['id' => $ruta->id, 'nombre' => 'Ruta Actualizada']);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_ruta_sin_horarios_activos(): void
    {
        $ruta = $this->crearRuta();

        $resultado = $this->service->eliminar($ruta);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('rutas', [
            'id' => $ruta->id,
            'activo' => false,
        ]);
    }

    // ─── activas() ──────────────────────────────────────────────

    #[Test]
    public function activas_retorna_solo_rutas_activas(): void
    {
        $this->crearRuta(['activo' => true]);
        $this->crearRuta(['activo' => false]);

        $resultado = $this->service->activas($this->empresa->id);

        $this->assertGreaterThanOrEqual(1, $resultado->count());
    }

    // ─── calcularTarifa() ───────────────────────────────────────

    #[Test]
    public function calcular_tarifa_retorna_datos(): void
    {
        $ruta = $this->crearRuta(['tarifa_base' => 5000]);

        $resultado = $this->service->calcularTarifa($ruta, 2);

        $this->assertArrayHasKey('tarifa_base_unitaria', $resultado);
        $this->assertArrayHasKey('cantidad_pasajeros', $resultado);
        $this->assertArrayHasKey('tarifa_final', $resultado);
        $this->assertEquals('10,000.00', $resultado['tarifa_final']);
    }

    // ─── estadisticas() ─────────────────────────────────────────

    #[Test]
    public function estadisticas_retorna_estructura_correcta(): void
    {
        $ruta = $this->crearRuta();

        $resultado = $this->service->estadisticas($ruta);

        $this->assertArrayHasKey('total_viajes', $resultado);
        $this->assertArrayHasKey('finalizados', $resultado);
        $this->assertArrayHasKey('en_curso', $resultado);
        $this->assertArrayHasKey('programados', $resultado);
    }
}
