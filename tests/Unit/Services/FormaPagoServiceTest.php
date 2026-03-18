<?php

namespace Tests\Unit\Services;

use App\Models\FormaPago;
use App\Services\FormaPagoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FormaPagoServiceTest extends TestCase
{
    protected FormaPagoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FormaPagoService();
    }

    private function crearFormaPago(array $override = []): FormaPago
    {
        return FormaPago::create(array_merge([
            'codigo_dgt' => 'FP' . strtoupper(substr(uniqid(), -3)),
            'nombre' => 'Forma Pago ' . uniqid(),
            'descripcion' => 'Descripción test',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listarTodos() ──────────────────────────────────────────

    #[Test]
    public function listar_todos_retorna_coleccion(): void
    {
        $this->crearFormaPago();
        $this->crearFormaPago();

        $resultado = $this->service->listarTodos();

        $this->assertGreaterThanOrEqual(2, $resultado->count());
    }

    #[Test]
    public function listar_todos_filtra_por_activo(): void
    {
        $this->crearFormaPago(['activo' => true]);
        $this->crearFormaPago(['activo' => false]);

        $resultado = $this->service->listarTodos(['activo' => true]);

        foreach ($resultado as $fp) {
            $this->assertTrue((bool) $fp->activo);
        }
    }

    #[Test]
    public function listar_todos_busca_por_nombre(): void
    {
        $this->crearFormaPago(['nombre' => 'Efectivo Especial', 'codigo_dgt' => 'EFE1']);
        $this->crearFormaPago(['nombre' => 'Tarjeta Especial', 'codigo_dgt' => 'TAR1']);

        $resultado = $this->service->listarTodos(['search' => 'Efectivo']);

        $this->assertCount(1, $resultado);
        $this->assertEquals('Efectivo Especial', $resultado->first()->nombre);
    }

    #[Test]
    public function listar_todos_busca_por_codigo_dgt(): void
    {
        $this->crearFormaPago(['nombre' => 'Cheque Busqueda', 'codigo_dgt' => 'CHQ9']);

        $resultado = $this->service->listarTodos(['search' => 'CHQ9']);

        $this->assertCount(1, $resultado);
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_forma_pago_exitosamente(): void
    {
        $data = [
            'codigo_dgt' => 'NEW1',
            'nombre' => 'Forma Pago Nueva ' . uniqid(),
            'descripcion' => 'Descripción nueva',
            'activo' => true,
        ];

        $fp = $this->service->crear($data);

        $this->assertInstanceOf(FormaPago::class, $fp);
        $this->assertDatabaseHas('formas_pago', ['codigo_dgt' => 'NEW1']);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_forma_pago_existente(): void
    {
        $fp = $this->crearFormaPago();

        $resultado = $this->service->obtener($fp->id);

        $this->assertEquals($fp->id, $resultado->id);
    }

    #[Test]
    public function obtener_forma_pago_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_forma_pago_exitosamente(): void
    {
        $fp = $this->crearFormaPago();

        $resultado = $this->service->actualizar($fp, ['descripcion' => 'Actualizada']);

        $this->assertEquals('Actualizada', $resultado->descripcion);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_forma_pago_marca_inactivo_y_eliminado(): void
    {
        $fp = $this->crearFormaPago();

        $resultado = $this->service->eliminar($fp);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('formas_pago', [
            'id' => $fp->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }
}
