<?php

namespace Tests\Unit\Services;

use App\Models\Empleado;
use App\Models\Empresa;
use App\Services\EmpleadoService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class EmpleadoServiceTest extends TestCase
{
    protected EmpleadoService $service;
    protected Empresa $empresa;
    protected int $cargoId;
    protected int $departamentoId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPermisos();

        $this->service = new EmpleadoService();
        $this->empresa = $this->createEmpresa();

        // Crear cargo y departamento requeridos por FK
        $this->cargoId = \DB::table('cargos')->insertGetId([
            'nombre' => 'Desarrollador',
            'descripcion' => 'Desarrollador de software',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->departamentoId = \DB::table('departamentos')->insertGetId([
            'nombre' => 'Tecnología',
            'descripcion' => 'Departamento de TI',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function crearEmpleado(array $override = []): Empleado
    {
        return Empleado::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Carlos',
            'primer_apellido' => 'Rodríguez',
            'segundo_apellido' => 'Mora',
            'tipo_documento' => 'cedula',
            'numero_documento' => uniqid('DOC-'),
            'fecha_nacimiento' => '1990-05-15',
            'fecha_ingreso' => '2024-01-10',
            'cargo_id' => $this->cargoId,
            'departamento_id' => $this->departamentoId,
            'salario' => 1500000.00,
            'email' => 'carlos' . uniqid() . '@test.com',
            'telefono' => '8888-8888',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    /** @test */
    public function listar_retorna_paginacion(): void
    {
        $this->crearEmpleado();
        $this->crearEmpleado(['nombre' => 'Ana']);

        $resultado = $this->service->listar();

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    /** @test */
    public function listar_filtra_por_departamento(): void
    {
        $otroDptoId = \DB::table('departamentos')->insertGetId([
            'nombre' => 'Ventas',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->crearEmpleado(['departamento_id' => $this->departamentoId]);
        $this->crearEmpleado(['departamento_id' => $otroDptoId]);

        $resultado = $this->service->listar(['departamento_id' => $this->departamentoId]);

        foreach ($resultado->items() as $emp) {
            $this->assertEquals($this->departamentoId, $emp->departamento_id);
        }
    }

    /** @test */
    public function listar_filtra_por_cargo(): void
    {
        $otroCargoId = \DB::table('cargos')->insertGetId([
            'nombre' => 'Diseñador',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->crearEmpleado(['cargo_id' => $this->cargoId]);
        $this->crearEmpleado(['cargo_id' => $otroCargoId]);

        $resultado = $this->service->listar(['cargo_id' => $this->cargoId]);

        foreach ($resultado->items() as $emp) {
            $this->assertEquals($this->cargoId, $emp->cargo_id);
        }
    }

    /** @test */
    public function listar_busca_por_nombre(): void
    {
        $this->crearEmpleado(['nombre' => 'Alejandro']);
        $this->crearEmpleado(['nombre' => 'María']);

        $resultado = $this->service->listar(['search' => 'Alejandro']);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
        $this->assertEquals('Alejandro', $resultado->first()->nombre);
    }

    /** @test */
    public function listar_busca_por_numero_documento(): void
    {
        $emp = $this->crearEmpleado(['numero_documento' => 'DOC-UNICO-123']);

        $resultado = $this->service->listar(['search' => 'DOC-UNICO-123']);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
    }

    // ─── crear() ────────────────────────────────────────────────

    /** @test */
    public function crear_empleado_exitosamente(): void
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Luis',
            'primer_apellido' => 'Jiménez',
            'tipo_documento' => 'cedula',
            'numero_documento' => '1-2345-6789',
            'fecha_nacimiento' => '1985-03-20',
            'fecha_ingreso' => '2024-06-01',
            'cargo_id' => $this->cargoId,
            'departamento_id' => $this->departamentoId,
            'salario' => 2000000.00,
        ];

        $empleado = $this->service->crear($data);

        $this->assertInstanceOf(Empleado::class, $empleado);
        $this->assertEquals('Luis', $empleado->nombre);
        $this->assertDatabaseHas('empleados', [
            'nombre' => 'Luis',
            'numero_documento' => '1-2345-6789',
        ]);
    }

    /** @test */
    public function crear_empleado_carga_relaciones(): void
    {
        $empleado = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Test',
            'primer_apellido' => 'Relations',
            'tipo_documento' => 'pasaporte',
            'numero_documento' => 'PASS-' . uniqid(),
            'cargo_id' => $this->cargoId,
            'departamento_id' => $this->departamentoId,
            'salario' => 500000,
        ]);

        $this->assertTrue($empleado->relationLoaded('departamento'));
        $this->assertTrue($empleado->relationLoaded('cargo'));
    }

    // ─── obtener() ──────────────────────────────────────────────

    /** @test */
    public function obtener_empleado_existente(): void
    {
        $empleado = $this->crearEmpleado();

        $resultado = $this->service->obtener($empleado->id);

        $this->assertEquals($empleado->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('cargo'));
        $this->assertTrue($resultado->relationLoaded('departamento'));
    }

    /** @test */
    public function obtener_empleado_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    /** @test */
    public function actualizar_empleado_exitosamente(): void
    {
        $empleado = $this->crearEmpleado(['salario' => 1000000.00]);

        $resultado = $this->service->actualizar($empleado, ['salario' => 1500000.00]);

        $this->assertEquals(1500000.00, (float) $resultado->salario);
        $this->assertDatabaseHas('empleados', ['id' => $empleado->id, 'salario' => 1500000.00]);
    }

    /** @test */
    public function actualizar_nombre_empleado(): void
    {
        $empleado = $this->crearEmpleado(['nombre' => 'Viejo']);

        $resultado = $this->service->actualizar($empleado, ['nombre' => 'Nuevo']);

        $this->assertEquals('Nuevo', $resultado->nombre);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    /** @test */
    public function eliminar_empleado(): void
    {
        $empleado = $this->crearEmpleado();

        $resultado = $this->service->eliminar($empleado);

        $this->assertTrue($resultado);
    }
}
