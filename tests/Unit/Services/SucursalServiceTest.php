<?php

namespace Tests\Unit\Services;

use App\Models\Empresa;
use App\Models\Sucursal;
use App\Services\SucursalService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SucursalServiceTest extends TestCase
{
    protected SucursalService $service;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SucursalService();
        $this->empresa = $this->createEmpresa();
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginado(): void
    {
        $this->createSucursal($this->empresa);
        $this->createSucursal($this->empresa, ['nombre' => 'Sucursal 2', 'email' => 's2@test.com']);

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id]);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_filtra_por_activo(): void
    {
        $this->createSucursal($this->empresa, ['activo' => true]);
        $this->createSucursal($this->empresa, [
            'nombre' => 'Inactiva',
            'activo' => false,
            'email' => 'inactiva@test.com',
        ]);

        $resultado = $this->service->listar([
            'empresa_id' => $this->empresa->id,
            'activo' => true,
        ]);

        foreach ($resultado as $suc) {
            $this->assertTrue((bool) $suc->activo);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_sucursal_exitosamente(): void
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Nueva Sucursal',
            'direccion' => 'Heredia',
            'activo' => true,
        ];

        $sucursal = $this->service->crear($data);

        $this->assertInstanceOf(Sucursal::class, $sucursal);
        $this->assertDatabaseHas('sucursales', ['id' => $sucursal->id, 'nombre' => 'Nueva Sucursal']);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_sucursal_existente(): void
    {
        $sucursal = $this->createSucursal($this->empresa);

        $resultado = $this->service->obtener($sucursal->id);

        $this->assertEquals($sucursal->id, $resultado->id);
    }

    #[Test]
    public function obtener_sucursal_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_sucursal_exitosamente(): void
    {
        $sucursal = $this->createSucursal($this->empresa);

        $resultado = $this->service->actualizar($sucursal, ['nombre' => 'Actualizada']);

        $this->assertEquals('Actualizada', $resultado->nombre);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_sucursal_no_principal(): void
    {
        $sucursal = $this->createSucursal($this->empresa, ['es_principal' => false]);

        $resultado = $this->service->eliminar($sucursal);

        $this->assertTrue($resultado);
    }

}
