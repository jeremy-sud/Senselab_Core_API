<?php

namespace Tests\Unit\Services;

use App\Models\BusUnidad;
use App\Models\Empresa;
use App\Models\ModeloBus;
use App\Services\BusUnidadService;
use App\Exceptions\BusinessException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BusUnidadServiceTest extends TestCase
{
    protected BusUnidadService $service;
    protected Empresa $empresa;
    protected ModeloBus $modelo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BusUnidadService();
        $this->empresa = $this->createEmpresa();
        $this->modelo = ModeloBus::create(['nombre' => 'Modelo Test']);
    }

    private function crearBus(array $override = []): BusUnidad
    {
        return BusUnidad::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'placa' => strtoupper('BUS-' . uniqid()),
            'modelo_id' => $this->modelo->id,
            'capacidad_asientos' => 40,
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listarTodos() ──────────────────────────────────────────

    #[Test]
    public function listar_todos_retorna_coleccion(): void
    {
        $this->crearBus();
        $this->crearBus();

        $resultado = $this->service->listarTodos(['empresa_id' => $this->empresa->id]);

        $this->assertGreaterThanOrEqual(2, $resultado->count());
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_bus_exitosamente(): void
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'placa' => 'NEW-' . uniqid(),
            'modelo_id' => $this->modelo->id,
            'capacidad_asientos' => 50,
            'activo' => true,
        ];

        $bus = $this->service->crear($data);

        $this->assertInstanceOf(BusUnidad::class, $bus);
        $this->assertDatabaseHas('buses_unidades', ['id' => $bus->id]);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_bus_existente(): void
    {
        $bus = $this->crearBus();

        $resultado = $this->service->obtener($bus->id);

        $this->assertEquals($bus->id, $resultado->id);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_bus_exitosamente(): void
    {
        $bus = $this->crearBus();

        $resultado = $this->service->actualizar($bus, ['capacidad_asientos' => 55]);

        $this->assertEquals(55, $resultado->capacidad_asientos);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_bus_sin_horarios_activos(): void
    {
        $bus = $this->crearBus();

        $resultado = $this->service->eliminar($bus);

        $this->assertTrue($resultado);
    }

    // ─── disponibles() ──────────────────────────────────────────

    #[Test]
    public function disponibles_retorna_buses_activos(): void
    {
        $this->crearBus(['activo' => true]);
        $this->crearBus(['activo' => false]);

        $resultado = $this->service->disponibles($this->empresa->id);

        foreach ($resultado as $bus) {
            $this->assertTrue((bool) $bus->activo);
        }
    }

    // ─── resumenFlota() ─────────────────────────────────────────

    #[Test]
    public function resumen_flota_retorna_estructura(): void
    {
        $this->crearBus();

        $resultado = $this->service->resumenFlota($this->empresa->id);

        $this->assertArrayHasKey('total_buses', $resultado);
        $this->assertArrayHasKey('buses_activos', $resultado);
        $this->assertGreaterThanOrEqual(1, $resultado['total_buses']);
    }

    // ─── porModelo() ────────────────────────────────────────────

    #[Test]
    public function por_modelo_retorna_buses_del_modelo(): void
    {
        $this->crearBus();

        $resultado = $this->service->porModelo($this->empresa->id, $this->modelo->id);

        $this->assertGreaterThanOrEqual(1, $resultado->count());
    }
}
