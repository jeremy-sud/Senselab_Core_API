<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\EntradaInventario;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EntradaInventarioTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected Almacen $almacen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;
        $this->almacen = Almacen::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Almacén Principal',
            'codigo' => 'ALM-001',
            'activo' => true,
        ]);
    }

    private function crearEntrada(array $overrides = []): EntradaInventario
    {
        return EntradaInventario::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'almacen_id' => $this->almacen->id,
            'fecha_entrada' => now(),
            'tipo_entrada' => 'Compra',
            'estado' => 'pendiente',
            'monto_total' => 0,
        ], $overrides));
    }

    private function crearProducto(string $codigo = 'PROD-001'): Producto
    {
        return Producto::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => $codigo,
            'nombre' => 'Producto Test ' . $codigo,
            'precio_compra' => 1000,
            'precio_venta' => 1500,
            'tipo_producto' => 'Producto',
            'activo' => true,
        ]);
    }

    #[Test]
    public function puede_listar_entradas_inventario()
    {
        $this->crearEntrada();
        $this->crearEntrada(['tipo_entrada' => 'Ajuste Positivo']);

        $response = $this->authenticatedJson('GET', '/api/entradas-inventario', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_entrada_inventario()
    {
        $producto = $this->crearProducto();

        $data = [
            'almacen_id' => $this->almacen->id,
            'fecha_entrada' => now()->format('Y-m-d'),
            'tipo_entrada' => 'Compra',
            'documento_referencia' => 'FAC-001',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 10,
                    'precio_unitario' => 1500.00,
                ],
            ],
        ];

        $response = $this->authenticatedJson('POST', '/api/entradas-inventario', $data, $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_entrada_inventario()
    {
        $entrada = $this->crearEntrada();

        $response = $this->authenticatedJson('GET', "/api/entradas-inventario/{$entrada->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_entrada_inventario()
    {
        $entrada = $this->crearEntrada();

        $response = $this->authenticatedJson('DELETE', "/api/entradas-inventario/{$entrada->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_almacen_requerido_al_crear()
    {
        $producto = $this->crearProducto();

        $data = [
            'fecha_entrada' => now()->format('Y-m-d'),
            'tipo_entrada' => 'Compra',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 5,
                    'precio_unitario' => 1000,
                ],
            ],
        ];

        $response = $this->authenticatedJson('POST', '/api/entradas-inventario', $data, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['almacen_id']);
    }

    #[Test]
    public function validacion_detalles_requeridos_al_crear()
    {
        $data = [
            'almacen_id' => $this->almacen->id,
            'fecha_entrada' => now()->format('Y-m-d'),
            'tipo_entrada' => 'Compra',
        ];

        $response = $this->authenticatedJson('POST', '/api/entradas-inventario', $data, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['detalles']);
    }

    #[Test]
    public function validacion_tipo_entrada_invalido()
    {
        $producto = $this->crearProducto();

        $data = [
            'almacen_id' => $this->almacen->id,
            'fecha_entrada' => now()->format('Y-m-d'),
            'tipo_entrada' => 'tipo_invalido',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 5,
                    'precio_unitario' => 1000,
                ],
            ],
        ];

        $response = $this->authenticatedJson('POST', '/api/entradas-inventario', $data, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_entrada']);
    }

    #[Test]
    public function no_puede_acceder_sin_autenticacion()
    {
        $response = $this->getJson('/api/entradas-inventario');

        $response->assertUnauthorized();
    }
}
