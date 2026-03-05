<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Almacen;
use App\Models\Producto;
use App\Models\InventarioProducto;
use App\Models\EntradaInventario;
use App\Models\SalidaInventario;
use App\Models\Proveedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class InventarioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
    }

    // ========================================================================
    // ALMACENES
    // ========================================================================

    #[Test]
    public function test_puede_listar_almacenes()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        Almacen::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $this->createSucursal($empresa)->id,
            'nombre' => 'Almacén Central',
            'codigo' => 'ALM-001',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', '/api/almacenes', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_almacen()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $data = [
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Almacén Nuevo',
            'codigo' => 'ALM-NEW',
            'descripcion' => 'Almacén de prueba',
            'es_principal' => true,
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/almacenes', $data, $usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('almacenes', [
            'nombre' => 'Almacén Nuevo',
            'codigo' => 'ALM-NEW',
            'empresa_id' => $empresa->id,
        ]);
    }

    #[Test]
    public function test_puede_ver_almacen()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $almacen = Almacen::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Almacén Show',
            'codigo' => 'ALM-SHOW',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', "/api/almacenes/{$almacen->id}", [], $usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_puede_actualizar_almacen()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $almacen = Almacen::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Almacén Original',
            'codigo' => 'ALM-ORI',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('PUT', "/api/almacenes/{$almacen->id}", [
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Almacén Actualizado',
        ], $usuario);

        $response->assertStatus(200);
        $this->assertDatabaseHas('almacenes', [
            'id' => $almacen->id,
            'nombre' => 'Almacén Actualizado',
        ]);
    }

    #[Test]
    public function test_puede_eliminar_almacen()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $almacen = Almacen::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Almacén a Eliminar',
            'codigo' => 'ALM-DEL',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/almacenes/{$almacen->id}", [], $usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_no_permite_crear_almacen_sin_autenticacion()
    {
        $response = $this->postJson('/api/almacenes', [
            'nombre' => 'Sin Auth',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function test_validacion_almacen_sin_nombre()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $response = $this->authenticatedJson('POST', '/api/almacenes', [
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            // nombre omitido
        ], $usuario);

        $response->assertStatus(422);
    }

    // ========================================================================
    // ENTRADAS DE INVENTARIO
    // ========================================================================

    #[Test]
    public function test_puede_listar_entradas_inventario()
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/entradas-inventario', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_entrada_inventario()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $almacen = Almacen::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Almacén Entradas',
            'codigo' => 'ALM-ENT',
            'activo' => true,
            'eliminado' => false,
        ]);

        $producto = $this->createProducto([], $empresa);

        $data = [
            'empresa_id' => $empresa->id,
            'almacen_id' => $almacen->id,
            'fecha_entrada' => now()->format('Y-m-d'),
            'tipo_entrada' => 'Compra',
            'estado' => 'Pendiente',
            'observaciones' => 'Observaciones de test',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 10,
                    'precio_unitario' => 500.00,
                ],
            ],
        ];

        $response = $this->authenticatedJson('POST', '/api/entradas-inventario', $data, $usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('entradas_inventario', [
            'almacen_id' => $almacen->id,
            'tipo_entrada' => 'Compra',
        ]);
    }

    #[Test]
    public function test_puede_ver_entrada_inventario()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $almacen = Almacen::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Almacén Show Ent',
            'codigo' => 'ALM-SE',
            'activo' => true,
            'eliminado' => false,
        ]);

        $entrada = EntradaInventario::create([
            'empresa_id' => $empresa->id,
            'almacen_id' => $almacen->id,
            'fecha_entrada' => now(),
            'tipo_entrada' => 'compra',
            'estado' => 'pendiente',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', "/api/entradas-inventario/{$entrada->id}", [], $usuario);

        $response->assertStatus(200);
    }

    // ========================================================================
    // SALIDAS DE INVENTARIO
    // ========================================================================

    #[Test]
    public function test_puede_listar_salidas_inventario()
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/salidas-inventario', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_salida_inventario()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $almacen = Almacen::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'nombre' => 'Almacén Salidas',
            'codigo' => 'ALM-SAL',
            'activo' => true,
            'eliminado' => false,
        ]);

        $producto = $this->createProducto([], $empresa);

        $data = [
            'empresa_id' => $empresa->id,
            'almacen_id' => $almacen->id,
            'fecha_salida' => now()->format('Y-m-d'),
            'tipo_salida' => 'Venta',
            'estado' => 'Pendiente',
            'observaciones' => 'Observaciones de test',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 5,
                    'costo_unitario' => 500.00,
                ],
            ],
        ];

        $response = $this->authenticatedJson('POST', '/api/salidas-inventario', $data, $usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('salidas_inventario', [
            'almacen_id' => $almacen->id,
            'tipo_salida' => 'Venta',
        ]);
    }
}
