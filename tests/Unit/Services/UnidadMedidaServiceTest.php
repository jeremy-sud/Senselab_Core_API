<?php

namespace Tests\Unit\Services;

use App\Models\UnidadMedida;
use App\Services\UnidadMedidaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnidadMedidaServiceTest extends TestCase
{
    protected UnidadMedidaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UnidadMedidaService();
    }

    private function crearUnidad(array $override = []): UnidadMedida
    {
        return UnidadMedida::create(array_merge([
            'codigo_dgt' => 'U' . strtoupper(substr(uniqid(), -4)),
            'nombre' => 'Unidad ' . uniqid(),
            'descripcion' => 'Descripción test',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listarTodos() ──────────────────────────────────────────

    #[Test]
    public function listar_todos_retorna_coleccion(): void
    {
        $this->crearUnidad();
        $this->crearUnidad();

        $resultado = $this->service->listarTodos();

        $this->assertGreaterThanOrEqual(2, $resultado->count());
    }

    #[Test]
    public function listar_todos_filtra_por_activo(): void
    {
        $this->crearUnidad(['activo' => true]);
        $this->crearUnidad(['activo' => false]);

        $resultado = $this->service->listarTodos(['activo' => true]);

        foreach ($resultado as $unidad) {
            $this->assertTrue((bool) $unidad->activo);
        }
    }

    #[Test]
    public function listar_todos_aplica_busqueda(): void
    {
        $this->crearUnidad(['nombre' => 'Kilogramo', 'codigo_dgt' => 'KG01']);
        $this->crearUnidad(['nombre' => 'Litro', 'codigo_dgt' => 'LT01']);

        $resultado = $this->service->listarTodos(['search' => 'Kilogramo']);

        $this->assertCount(1, $resultado);
        $this->assertEquals('Kilogramo', $resultado->first()->nombre);
    }

    #[Test]
    public function listar_todos_busca_por_codigo_dgt(): void
    {
        $this->crearUnidad(['nombre' => 'Metro', 'codigo_dgt' => 'MTR9']);

        $resultado = $this->service->listarTodos(['search' => 'MTR9']);

        $this->assertCount(1, $resultado);
        $this->assertEquals('MTR9', $resultado->first()->codigo_dgt);
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $this->crearUnidad();
        $this->crearUnidad();

        $resultado = $this->service->listar([], 10);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_unidad_exitosamente(): void
    {
        $data = [
            'codigo_dgt' => 'NWU1',
            'nombre' => 'Nueva Unidad',
            'descripcion' => 'Descripción nueva',
            'activo' => true,
        ];

        $unidad = $this->service->crear($data);

        $this->assertInstanceOf(UnidadMedida::class, $unidad);
        $this->assertEquals('Nueva Unidad', $unidad->nombre);
        $this->assertDatabaseHas('unidades_medida', ['codigo_dgt' => 'NWU1']);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_unidad_existente(): void
    {
        $unidad = $this->crearUnidad();

        $resultado = $this->service->obtener($unidad->id);

        $this->assertEquals($unidad->id, $resultado->id);
    }

    #[Test]
    public function obtener_unidad_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_unidad_exitosamente(): void
    {
        $unidad = $this->crearUnidad(['nombre' => 'Nombre Viejo', 'codigo_dgt' => 'NV01']);

        $resultado = $this->service->actualizar($unidad, ['descripcion' => 'Actualizada']);

        $this->assertEquals('Actualizada', $resultado->descripcion);
        $this->assertDatabaseHas('unidades_medida', ['id' => $unidad->id, 'descripcion' => 'Actualizada']);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_unidad_marca_inactivo_y_eliminado(): void
    {
        $unidad = $this->crearUnidad();

        $resultado = $this->service->eliminar($unidad);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('unidades_medida', [
            'id' => $unidad->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }

    // ─── ordenamiento ───────────────────────────────────────────

    #[Test]
    public function listar_todos_aplica_ordenamiento_custom(): void
    {
        $this->crearUnidad(['nombre' => 'Z Unidad', 'codigo_dgt' => 'ZU01']);
        $this->crearUnidad(['nombre' => 'A Unidad', 'codigo_dgt' => 'AU01']);

        $resultado = $this->service->listarTodos(['sort_by' => 'nombre', 'sort_order' => 'asc']);

        $nombres = $resultado->pluck('nombre')->toArray();
        $this->assertEquals($nombres, collect($nombres)->sort()->values()->toArray());
    }
}
