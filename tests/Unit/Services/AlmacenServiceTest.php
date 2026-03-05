<?php

namespace Tests\Unit\Services;

use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Services\AlmacenService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AlmacenServiceTest extends TestCase
{
    protected AlmacenService $service;
    protected Empresa $empresa;
    protected Sucursal $sucursal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPermisos();

        $this->service = new AlmacenService();
        $this->empresa = $this->createEmpresa();
        $this->sucursal = $this->createSucursal($this->empresa);
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function crearAlmacen(array $override = []): Almacen
    {
        return Almacen::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'sucursal_id' => $this->sucursal->id,
            'nombre' => 'Almacén Test ' . uniqid(),
            'codigo' => 'ALM-' . strtoupper(substr(uniqid(), -5)),
            'descripcion' => 'Almacén de prueba',
            'es_principal' => false,
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    /** @test */
    public function listar_retorna_paginacion(): void
    {
        $this->crearAlmacen();
        $this->crearAlmacen();

        $resultado = $this->service->listar([], 10);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    /** @test */
    public function listar_filtra_por_empresa_id(): void
    {
        $this->crearAlmacen();

        $otraEmpresa = $this->createEmpresa(['nombre' => 'Otra Empresa', 'email' => 'otra' . uniqid() . '@test.com']);
        $otraSucursal = $this->createSucursal($otraEmpresa);
        $this->crearAlmacen([
            'empresa_id' => $otraEmpresa->id,
            'sucursal_id' => $otraSucursal->id,
        ]);

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id]);

        foreach ($resultado->items() as $almacen) {
            $this->assertEquals($this->empresa->id, $almacen->empresa_id);
        }
    }

    /** @test */
    public function listar_filtra_por_sucursal_id(): void
    {
        $this->crearAlmacen();
        $otraSucursal = $this->createSucursal($this->empresa, ['nombre' => 'Sucursal 2']);
        $this->crearAlmacen(['sucursal_id' => $otraSucursal->id]);

        $resultado = $this->service->listar(['sucursal_id' => $this->sucursal->id]);

        foreach ($resultado->items() as $almacen) {
            $this->assertEquals($this->sucursal->id, $almacen->sucursal_id);
        }
    }

    /** @test */
    public function listar_filtra_solo_activos(): void
    {
        $this->crearAlmacen(['activo' => true]);
        $this->crearAlmacen(['activo' => false]);

        $resultado = $this->service->listar(['activos' => true]);

        foreach ($resultado->items() as $almacen) {
            $this->assertTrue((bool) $almacen->activo);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    /** @test */
    public function crear_almacen_exitosamente(): void
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'sucursal_id' => $this->sucursal->id,
            'nombre' => 'Almacén Nuevo',
            'codigo' => 'ALM-NUEVO',
            'descripcion' => 'Descripción test',
            'es_principal' => false,
            'activo' => true,
        ];

        $almacen = $this->service->crear($data);

        $this->assertInstanceOf(Almacen::class, $almacen);
        $this->assertEquals('Almacén Nuevo', $almacen->nombre);
        $this->assertDatabaseHas('almacenes', ['nombre' => 'Almacén Nuevo']);
    }

    /** @test */
    public function crear_almacen_principal_desmarca_otros(): void
    {
        // Crear un almacén principal existente
        $existente = $this->crearAlmacen(['es_principal' => true]);

        // Crear otro almacén principal en la misma sucursal
        $nuevo = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'sucursal_id' => $this->sucursal->id,
            'nombre' => 'Nuevo Principal',
            'codigo' => 'ALM-NPPAL',
            'es_principal' => true,
            'activo' => true,
        ]);

        // El anterior debe haberse desmarcado
        $existente->refresh();
        $this->assertFalse((bool) $existente->es_principal);
        $this->assertTrue((bool) $nuevo->es_principal);
    }

    /** @test */
    public function crear_almacen_carga_relaciones(): void
    {
        $almacen = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'sucursal_id' => $this->sucursal->id,
            'nombre' => 'Con Relaciones',
            'codigo' => 'ALM-REL',
            'activo' => true,
        ]);

        $this->assertTrue($almacen->relationLoaded('empresa'));
        $this->assertTrue($almacen->relationLoaded('sucursal'));
    }

    // ─── obtener() ──────────────────────────────────────────────

    /** @test */
    public function obtener_almacen_existente(): void
    {
        $almacen = $this->crearAlmacen();

        $resultado = $this->service->obtener($almacen->id);

        $this->assertEquals($almacen->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('empresa'));
        $this->assertTrue($resultado->relationLoaded('sucursal'));
    }

    /** @test */
    public function obtener_almacen_inexistente_lanza_excepcion(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    /** @test */
    public function actualizar_almacen_exitosamente(): void
    {
        $almacen = $this->crearAlmacen(['nombre' => 'Nombre Viejo']);

        $resultado = $this->service->actualizar($almacen, ['nombre' => 'Nombre Nuevo']);

        $this->assertEquals('Nombre Nuevo', $resultado->nombre);
        $this->assertDatabaseHas('almacenes', ['id' => $almacen->id, 'nombre' => 'Nombre Nuevo']);
    }

    /** @test */
    public function actualizar_a_principal_desmarca_otros(): void
    {
        $principal = $this->crearAlmacen(['es_principal' => true]);
        $otro = $this->crearAlmacen(['es_principal' => false]);

        $this->service->actualizar($otro, ['es_principal' => true]);

        $principal->refresh();
        $this->assertFalse((bool) $principal->es_principal);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    /** @test */
    public function eliminar_almacen_no_principal(): void
    {
        $almacen = $this->crearAlmacen(['es_principal' => false]);

        $resultado = $this->service->eliminar($almacen);

        $this->assertTrue($resultado);
        $this->assertDatabaseHas('almacenes', [
            'id' => $almacen->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }

    /** @test */
    public function eliminar_almacen_principal_lanza_excepcion(): void
    {
        $almacen = $this->crearAlmacen(['es_principal' => true]);

        $this->expectException(ValidationException::class);

        $this->service->eliminar($almacen);
    }
}
