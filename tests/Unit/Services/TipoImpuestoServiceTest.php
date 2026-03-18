<?php

namespace Tests\Unit\Services;

use App\Exceptions\BusinessException;
use App\Models\TipoImpuesto;
use App\Services\TipoImpuestoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TipoImpuestoServiceTest extends TestCase
{
    protected TipoImpuestoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TipoImpuestoService();
    }

    private function crearTipo(array $override = []): TipoImpuesto
    {
        return TipoImpuesto::create(array_merge([
            'codigo_hacienda' => 'TI' . strtoupper(substr(uniqid(), -4)),
            'nombre' => 'Tipo Impuesto ' . uniqid(),
            'descripcion' => 'Descripción test',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $this->crearTipo();
        $this->crearTipo();

        $resultado = $this->service->listar([], 10);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_excluye_eliminados(): void
    {
        $this->crearTipo(['eliminado' => false]);
        $this->crearTipo(['eliminado' => true]);

        $resultado = $this->service->listar();

        foreach ($resultado->items() as $tipo) {
            $this->assertFalse((bool) $tipo->eliminado);
        }
    }

    #[Test]
    public function listar_filtra_por_activo(): void
    {
        $this->crearTipo(['activo' => true]);
        $this->crearTipo(['activo' => false]);

        $resultado = $this->service->listar(['activo' => true]);

        foreach ($resultado->items() as $tipo) {
            $this->assertTrue((bool) $tipo->activo);
        }
    }

    #[Test]
    public function listar_busca_con_clave_buscar(): void
    {
        $this->crearTipo(['nombre' => 'IVA Especial', 'codigo_hacienda' => 'IVAE']);
        $this->crearTipo(['nombre' => 'ISC General', 'codigo_hacienda' => 'ISCG']);

        $resultado = $this->service->listar(['buscar' => 'IVA']);

        $this->assertEquals(1, $resultado->total());
        $this->assertEquals('IVA Especial', $resultado->items()[0]->nombre);
    }

    #[Test]
    public function listar_busca_con_clave_search(): void
    {
        $this->crearTipo(['nombre' => 'Impuesto Selectivo', 'codigo_hacienda' => 'SEL1']);

        $resultado = $this->service->listar(['search' => 'Selectivo']);

        $this->assertEquals(1, $resultado->total());
    }

    #[Test]
    public function listar_busca_por_codigo_hacienda(): void
    {
        $this->crearTipo(['nombre' => 'Test Buscar Codigo', 'codigo_hacienda' => 'XYZ9']);

        $resultado = $this->service->listar(['buscar' => 'XYZ9']);

        $this->assertEquals(1, $resultado->total());
    }

    // ─── activos() ──────────────────────────────────────────────

    #[Test]
    public function activos_retorna_solo_activos_no_eliminados(): void
    {
        $this->crearTipo(['activo' => true, 'eliminado' => false]);
        $this->crearTipo(['activo' => false, 'eliminado' => false]);
        $this->crearTipo(['activo' => true, 'eliminado' => true]);

        $resultado = $this->service->activos();

        foreach ($resultado as $tipo) {
            $this->assertTrue((bool) $tipo->activo);
            $this->assertFalse((bool) $tipo->eliminado);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_tipo_impuesto_exitosamente(): void
    {
        $data = [
            'codigo_hacienda' => 'NW01',
            'nombre' => 'Tipo Nuevo',
            'descripcion' => 'Desc nueva',
            'activo' => true,
        ];

        $tipo = $this->service->crear($data);

        $this->assertInstanceOf(TipoImpuesto::class, $tipo);
        $this->assertEquals('Tipo Nuevo', $tipo->nombre);
        $this->assertDatabaseHas('tipos_impuesto', ['codigo_hacienda' => 'NW01']);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_tipo_impuesto_existente(): void
    {
        $tipo = $this->crearTipo();

        $resultado = $this->service->obtener($tipo->id);

        $this->assertEquals($tipo->id, $resultado->id);
    }

    #[Test]
    public function obtener_tipo_eliminado_lanza_excepcion(): void
    {
        $tipo = $this->crearTipo(['eliminado' => true]);

        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener($tipo->id);
    }

    #[Test]
    public function obtener_tipo_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_tipo_impuesto_exitosamente(): void
    {
        $tipo = $this->crearTipo();

        $resultado = $this->service->actualizar($tipo, ['descripcion' => 'Actualizada']);

        $this->assertEquals('Actualizada', $resultado->descripcion);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_tipo_impuesto_normal(): void
    {
        $tipo = $this->crearTipo(['codigo_hacienda' => 'DEL1']);

        $resultado = $this->service->eliminar($tipo);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('tipos_impuesto', [
            'id' => $tipo->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }

    #[Test]
    public function eliminar_iva_lanza_business_exception(): void
    {
        $iva = $this->crearTipo(['codigo_hacienda' => '01', 'nombre' => 'IVA']);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('No se puede eliminar el tipo de impuesto IVA');

        $this->service->eliminar($iva);
    }

    #[Test]
    public function eliminar_iva_no_modifica_registro(): void
    {
        $iva = $this->crearTipo(['codigo_hacienda' => '01', 'nombre' => 'IVA']);

        try {
            $this->service->eliminar($iva);
        } catch (BusinessException) {
            // expected
        }

        $this->assertDatabaseHas('tipos_impuesto', [
            'id' => $iva->id,
            'activo' => true,
            'eliminado' => false,
        ]);
    }
}
