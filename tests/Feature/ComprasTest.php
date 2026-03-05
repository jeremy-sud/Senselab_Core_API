<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Proveedor;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\InventarioProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ComprasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
    }

    /**
     * Crea un Proveedor de prueba vinculado a una empresa
     */
    protected function createProveedor(int $empresaId, array $attrs = []): Proveedor
    {
        return Proveedor::create(array_merge([
            'empresa_id' => $empresaId,
            'tipo_identificacion' => 'juridica',
            'numero_identificacion' => '3101' . rand(100000, 999999),
            'nombre' => 'Proveedor Test S.A.',
            'nombre_comercial' => 'ProvTest',
            'email' => 'proveedor@test.com',
            'telefono' => '2222-3333',
            'activo' => true,
            'eliminado' => false,
        ], $attrs));
    }

    // ========================================================================
    // PROVEEDORES
    // ========================================================================

    #[Test]
    public function test_puede_listar_proveedores()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $this->createProveedor($empresa->id);

        $response = $this->authenticatedJson('GET', '/api/proveedores', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_proveedor()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $data = [
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => 'juridica',
            'numero_identificacion' => '3101999888',
            'nombre' => 'Proveedor Nuevo S.A.',
            'nombre_comercial' => 'ProvNuevo',
            'email' => 'nuevo@proveedor.com',
            'telefono' => '2222-4444',
            'direccion' => 'San José, Costa Rica',
            'limite_credito' => 500000.00,
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/proveedores', $data, $usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('proveedores', [
            'nombre' => 'Proveedor Nuevo S.A.',
            'numero_identificacion' => '3101999888',
            'empresa_id' => $empresa->id,
        ]);
    }

    #[Test]
    public function test_puede_ver_proveedor()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $proveedor = $this->createProveedor($empresa->id);

        $response = $this->authenticatedJson('GET', "/api/proveedores/{$proveedor->id}", [], $usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_puede_actualizar_proveedor()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $proveedor = $this->createProveedor($empresa->id);

        $response = $this->authenticatedJson('PUT', "/api/proveedores/{$proveedor->id}", [
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => 'juridica',
            'numero_identificacion' => $proveedor->numero_identificacion,
            'nombre' => 'Proveedor Actualizado S.A.',
        ], $usuario);

        $response->assertStatus(200);
        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'nombre' => 'Proveedor Actualizado S.A.',
        ]);
    }

    #[Test]
    public function test_puede_eliminar_proveedor()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $proveedor = $this->createProveedor($empresa->id);

        $response = $this->authenticatedJson('DELETE', "/api/proveedores/{$proveedor->id}", [], $usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_validacion_proveedor_sin_nombre()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $response = $this->authenticatedJson('POST', '/api/proveedores', [
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => 'juridica',
            'numero_identificacion' => '3101111222',
            // nombre omitido
        ], $usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_no_permite_proveedor_sin_autenticacion()
    {
        $response = $this->postJson('/api/proveedores', [
            'nombre' => 'Sin Auth',
        ]);

        $response->assertStatus(401);
    }

    // ========================================================================
    // ÓRDENES DE COMPRA
    // ========================================================================

    #[Test]
    public function test_puede_listar_ordenes_compra()
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/ordenes-compra', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_orden_compra()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $proveedor = $this->createProveedor($empresa->id);

        $producto = $this->createProducto([], $empresa);

        $data = [
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'usuario_id' => $usuario->id,
            'fecha_orden' => now()->format('Y-m-d'),
            'fecha_entrega_esperada' => now()->addDays(7)->format('Y-m-d'),
            'estado' => 'borrador',
            'observaciones' => 'Orden de compra de prueba',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 10,
                    'precio_unitario' => 5000.00,
                    'descuento' => 0,
                    'descripcion' => 'Compra de producto test',
                ],
            ],
        ];

        $response = $this->authenticatedJson('POST', '/api/ordenes-compra', $data, $usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function test_puede_ver_orden_compra()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $proveedor = $this->createProveedor($empresa->id);

        $orden = OrdenCompra::create([
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'usuario_id' => $usuario->id,
            'numero_orden' => 'OC-TEST-001',
            'fecha_orden' => now(),
            'estado' => 'borrador',
            'subtotal' => 50000,
            'impuesto_total' => 6500,
            'total_orden' => 56500,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', "/api/ordenes-compra/{$orden->id}", [], $usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_validacion_orden_compra_sin_proveedor()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $producto = $this->createProducto([], $empresa);

        $response = $this->authenticatedJson('POST', '/api/ordenes-compra', [
            'empresa_id' => $empresa->id,
            'usuario_id' => $usuario->id,
            'fecha_orden' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            // proveedor_id omitido
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 5,
                    'precio_unitario' => 1000,
                ],
            ],
        ], $usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_validacion_orden_compra_sin_detalles()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $proveedor = $this->createProveedor($empresa->id);

        $response = $this->authenticatedJson('POST', '/api/ordenes-compra', [
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'usuario_id' => $usuario->id,
            'fecha_orden' => now()->format('Y-m-d'),
            'estado' => 'borrador',
            // detalles omitidos
        ], $usuario);

        $response->assertStatus(422);
    }
}
