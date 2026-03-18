<?php

namespace Tests\Unit\Services;

use App\Models\Marca;
use App\Services\MarcaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MarcaServiceTest extends TestCase
{
    protected MarcaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarcaService();
    }

    private function crearMarca(array $override = []): Marca
    {
        return Marca::create(array_merge([
            'nombre' => 'Marca ' . uniqid(),
            'descripcion' => 'Descripción test',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listarTodos() ──────────────────────────────────────────

    #[Test]
    public function listar_todos_retorna_coleccion(): void
    {
        $this->crearMarca();
        $this->crearMarca();

        $resultado = $this->service->listarTodos();

        $this->assertGreaterThanOrEqual(2, $resultado->count());
    }

    #[Test]
    public function listar_todos_filtra_por_activo(): void
    {
        $this->crearMarca(['activo' => true]);
        $this->crearMarca(['activo' => false]);

        $resultado = $this->service->listarTodos(['activo' => true]);

        foreach ($resultado as $marca) {
            $this->assertTrue((bool) $marca->activo);
        }
    }

    #[Test]
    public function listar_todos_busca_por_nombre(): void
    {
        $this->crearMarca(['nombre' => 'Toyota']);
        $this->crearMarca(['nombre' => 'Honda']);

        $resultado = $this->service->listarTodos(['search' => 'Toyota']);

        $this->assertCount(1, $resultado);
        $this->assertEquals('Toyota', $resultado->first()->nombre);
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_marca_exitosamente(): void
    {
        $data = [
            'nombre' => 'Marca Nueva ' . uniqid(),
            'descripcion' => 'Descripción nueva',
            'activo' => true,
        ];

        $marca = $this->service->crear($data);

        $this->assertInstanceOf(Marca::class, $marca);
        $this->assertDatabaseHas('marcas', ['id' => $marca->id]);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_marca_existente(): void
    {
        $marca = $this->crearMarca();

        $resultado = $this->service->obtener($marca->id);

        $this->assertEquals($marca->id, $resultado->id);
    }

    #[Test]
    public function obtener_marca_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_marca_exitosamente(): void
    {
        $marca = $this->crearMarca();

        $resultado = $this->service->actualizar($marca, ['descripcion' => 'Actualizada']);

        $this->assertEquals('Actualizada', $resultado->descripcion);
        $this->assertDatabaseHas('marcas', ['id' => $marca->id, 'descripcion' => 'Actualizada']);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_marca_marca_inactivo_y_eliminado(): void
    {
        $marca = $this->crearMarca();

        $resultado = $this->service->eliminar($marca);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('marcas', [
            'id' => $marca->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }
}
