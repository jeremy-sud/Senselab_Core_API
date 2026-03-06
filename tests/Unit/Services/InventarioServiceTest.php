<?php

namespace Tests\Unit\Services;

use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\EntradaInventario;
use App\Models\SalidaInventario;
use App\Models\Usuario;
use App\Services\InventarioService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class InventarioServiceTest extends TestCase
{
    protected InventarioService $service;
    protected Empresa $empresa;
    protected Almacen $almacen;
    protected Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRoles();
        $this->seedPermisos();

        $this->service = new InventarioService();
        $this->empresa = $this->createEmpresa();
        $this->usuario = $this->createAdminUsuario();
        $this->actingAs($this->usuario, 'sanctum');

        $this->almacen = Almacen::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Almacén Principal',
            'codigo' => 'ALM-' . uniqid(),
            'es_principal' => true,
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    // ─── Helpers ────────────────────────────────────────────────

    private function crearEntrada(array $override = []): EntradaInventario
    {
        return EntradaInventario::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'almacen_id' => $this->almacen->id,
            'fecha_entrada' => now()->toDateTimeString(),
            'tipo_entrada' => 'Compra',
            'documento_referencia' => 'ENT-' . uniqid(),
            'estado' => 'Pendiente',
            'monto_total' => 150000.00,
            'observaciones' => 'Entrada de prueba',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    private function crearSalida(array $override = []): SalidaInventario
    {
        return SalidaInventario::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'almacen_id' => $this->almacen->id,
            'fecha_salida' => now()->toDateTimeString(),
            'tipo_salida' => 'Venta',
            'documento_referencia' => 'SAL-' . uniqid(),
            'estado' => 'Pendiente',
            'monto_total' => 75000.00,
            'observaciones' => 'Salida de prueba',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── listarEntradas() ───────────────────────────────────────

    #[Test]
    public function listar_entradas_retorna_coleccion(): void
    {
        $this->crearEntrada();
        $this->crearEntrada();

        $resultado = $this->service->listarEntradas($this->empresa->id);

        $this->assertGreaterThanOrEqual(2, $resultado->count());
    }

    #[Test]
    public function listar_entradas_filtra_por_almacen(): void
    {
        $this->crearEntrada();

        $otroAlmacen = Almacen::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Almacén Secundario',
            'codigo' => 'ALM2-' . uniqid(),
            'activo' => true,
            'eliminado' => false,
        ]);
        $this->crearEntrada(['almacen_id' => $otroAlmacen->id]);

        $resultado = $this->service->listarEntradas($this->empresa->id, ['almacen_id' => $this->almacen->id]);

        foreach ($resultado as $entrada) {
            $this->assertEquals($this->almacen->id, $entrada->almacen_id);
        }
    }

    #[Test]
    public function listar_entradas_filtra_por_estado(): void
    {
        $this->crearEntrada(['estado' => 'Pendiente']);
        $this->crearEntrada(['estado' => 'Procesada']);

        $resultado = $this->service->listarEntradas($this->empresa->id, ['estado' => 'Pendiente']);

        foreach ($resultado as $entrada) {
            $this->assertEquals('Pendiente', $entrada->estado);
        }
    }

    // ─── crearEntrada() ─────────────────────────────────────────

    #[Test]
    public function crear_entrada_exitosamente(): void
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'almacen_id' => $this->almacen->id,
            'fecha_entrada' => now()->toDateTimeString(),
            'tipo_entrada' => 'Compra',
            'documento_referencia' => 'ENT-TEST-001',
            'estado' => 'Pendiente',
            'monto_total' => 200000.00,
            'activo' => true,
            'eliminado' => false,
        ];

        $entrada = $this->service->crearEntrada($data);

        $this->assertInstanceOf(EntradaInventario::class, $entrada);
        $this->assertEquals('ENT-TEST-001', $entrada->documento_referencia);
        $this->assertTrue($entrada->relationLoaded('almacen'));
    }

    // ─── obtenerEntrada() ───────────────────────────────────────

    #[Test]
    public function obtener_entrada_existente(): void
    {
        $entrada = $this->crearEntrada();

        $resultado = $this->service->obtenerEntrada($this->empresa->id, $entrada->id);

        $this->assertEquals($entrada->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('almacen'));
    }

    #[Test]
    public function obtener_entrada_inexistente_lanza_excepcion(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->obtenerEntrada($this->empresa->id, 99999);
    }

    // ─── cancelarEntrada() ──────────────────────────────────────

    #[Test]
    public function cancelar_entrada_pendiente(): void
    {
        $entrada = $this->crearEntrada(['estado' => 'Pendiente']);

        $resultado = $this->service->cancelarEntrada($entrada);

        $this->assertEquals('Cancelada', $resultado->estado);
    }

    #[Test]
    public function cancelar_entrada_procesada_lanza_excepcion(): void
    {
        $entrada = $this->crearEntrada(['estado' => 'Procesada']);

        $this->expectException(ValidationException::class);

        $this->service->cancelarEntrada($entrada);
    }

    // ─── listarSalidas() ────────────────────────────────────────

    #[Test]
    public function listar_salidas_retorna_coleccion(): void
    {
        $this->crearSalida();
        $this->crearSalida();

        $resultado = $this->service->listarSalidas($this->empresa->id);

        $this->assertGreaterThanOrEqual(2, $resultado->count());
    }

    #[Test]
    public function listar_salidas_filtra_por_almacen(): void
    {
        $this->crearSalida();

        $otroAlmacen = Almacen::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Almacén Otro',
            'codigo' => 'ALM3-' . uniqid(),
            'activo' => true,
            'eliminado' => false,
        ]);
        $this->crearSalida(['almacen_id' => $otroAlmacen->id]);

        $resultado = $this->service->listarSalidas($this->empresa->id, ['almacen_id' => $this->almacen->id]);

        foreach ($resultado as $salida) {
            $this->assertEquals($this->almacen->id, $salida->almacen_id);
        }
    }

    #[Test]
    public function listar_salidas_filtra_por_estado(): void
    {
        $this->crearSalida(['estado' => 'Pendiente']);
        $this->crearSalida(['estado' => 'Procesada']);

        $resultado = $this->service->listarSalidas($this->empresa->id, ['estado' => 'Pendiente']);

        foreach ($resultado as $salida) {
            $this->assertEquals('Pendiente', $salida->estado);
        }
    }

    // ─── crearSalida() ──────────────────────────────────────────

    #[Test]
    public function crear_salida_exitosamente(): void
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'almacen_id' => $this->almacen->id,
            'fecha_salida' => now()->toDateTimeString(),
            'tipo_salida' => 'Venta',
            'documento_referencia' => 'SAL-TEST-001',
            'estado' => 'Pendiente',
            'monto_total' => 50000.00,
            'activo' => true,
            'eliminado' => false,
        ];

        $salida = $this->service->crearSalida($data);

        $this->assertInstanceOf(SalidaInventario::class, $salida);
        $this->assertEquals('SAL-TEST-001', $salida->documento_referencia);
        $this->assertTrue($salida->relationLoaded('almacen'));
    }

    // ─── obtenerSalida() ────────────────────────────────────────

    #[Test]
    public function obtener_salida_existente(): void
    {
        $salida = $this->crearSalida();

        $resultado = $this->service->obtenerSalida($this->empresa->id, $salida->id);

        $this->assertEquals($salida->id, $resultado->id);
        $this->assertTrue($resultado->relationLoaded('almacen'));
    }

    #[Test]
    public function obtener_salida_inexistente_lanza_excepcion(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->service->obtenerSalida($this->empresa->id, 99999);
    }

    // ─── cancelarSalida() ───────────────────────────────────────

    #[Test]
    public function cancelar_salida_pendiente(): void
    {
        $salida = $this->crearSalida(['estado' => 'Pendiente']);

        $resultado = $this->service->cancelarSalida($salida);

        $this->assertEquals('Cancelada', $resultado->estado);
    }

    #[Test]
    public function cancelar_salida_procesada_lanza_excepcion(): void
    {
        $salida = $this->crearSalida(['estado' => 'Procesada']);

        $this->expectException(ValidationException::class);

        $this->service->cancelarSalida($salida);
    }
}
