<?php

namespace Tests\Unit\Services;

use App\Models\ModeloBus;
use App\Services\ModeloBusService;
use App\Exceptions\BusinessException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModeloBusServiceTest extends TestCase
{
    protected ModeloBusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ModeloBusService();
    }

    private function crearModeloBus(array $override = []): ModeloBus
    {
        return ModeloBus::create(array_merge([
            'nombre' => 'Modelo ' . uniqid(),
        ], $override));
    }

    // ─── listarTodos() ──────────────────────────────────────────

    #[Test]
    public function listar_todos_retorna_coleccion(): void
    {
        $this->crearModeloBus();
        $this->crearModeloBus();

        $resultado = $this->service->listarTodos();

        $this->assertGreaterThanOrEqual(2, $resultado->count());
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_modelo_exitosamente(): void
    {
        $data = ['nombre' => 'Modelo Nuevo ' . uniqid()];

        $modelo = $this->service->crear($data);

        $this->assertInstanceOf(ModeloBus::class, $modelo);
        $this->assertDatabaseHas('modelos_buses', ['id' => $modelo->id]);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_modelo_existente(): void
    {
        $modelo = $this->crearModeloBus();

        $resultado = $this->service->obtener($modelo->id);

        $this->assertEquals($modelo->id, $resultado->id);
    }

    #[Test]
    public function obtener_modelo_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_modelo_exitosamente(): void
    {
        $modelo = $this->crearModeloBus();

        $resultado = $this->service->actualizar($modelo, ['nombre' => 'Actualizado']);

        $this->assertEquals('Actualizado', $resultado->nombre);
        $this->assertDatabaseHas('modelos_buses', ['id' => $modelo->id, 'nombre' => 'Actualizado']);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_modelo_sin_buses_asociados(): void
    {
        $modelo = $this->crearModeloBus();

        $resultado = $this->service->eliminar($modelo);

        $this->assertTrue($resultado);
    }

    #[Test]
    public function eliminar_modelo_con_buses_lanza_excepcion(): void
    {
        $modelo = $this->crearModeloBus();
        $empresa = $this->createEmpresa();

        \App\Models\BusUnidad::create([
            'empresa_id' => $empresa->id,
            'placa' => 'ABC-' . rand(1000, 9999),
            'modelo_id' => $modelo->id,
            'capacidad_asientos' => 40,
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->expectException(BusinessException::class);

        $this->service->eliminar($modelo);
    }

    // ─── activos() ──────────────────────────────────────────────

    #[Test]
    public function activos_retorna_modelos(): void
    {
        $this->crearModeloBus();

        $resultado = $this->service->activos();

        $this->assertGreaterThanOrEqual(1, $resultado->count());
    }
}
