<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Almacen;
use App\Models\Usuario;
use App\Models\InventarioProducto;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
    }

    /** @test */
    public function test_puede_crear_venta_simple()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        // Crear cliente
        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '1-1234-5678',
            'nombre' => 'Cliente',
            'apellidos' => 'Test',
            'email' => 'cliente@test.com',
            'telefono' => '8888-9999',
            'activo' => true,
            'eliminado' => false
        ]);

        // Crear producto con stock
        $almacen = Almacen::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Almacén Principal',
            'codigo' => 'ALM-001',
            'activo' => true,
            'eliminado' => false
        ]);

        $producto = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'PROD-001',
            'nombre' => 'Producto Test',
            'precio_venta' => 10000.00,
            'tiene_iva' => true,
            'porcentaje_impuesto' => 13.00,
            'activo' => true,
            'eliminado' => false
        ]);

        // Agregar stock
        InventarioProducto::create([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad_actual' => 100,
            'cantidad_minima' => 10
        ]);

        $formaPago = $this->getFormaPago();

        $ventaData = [
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'usuario_id' => $usuario->id,
            'forma_pago_id' => $formaPago->id,
            'almacen_id' => $almacen->id,
            'fecha_venta' => now()->format('Y-m-d'),
            'tipo_comprobante' => 'factura',
            'tipo_pago' => 'Contado',
            'estado' => 'Completada',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 5,
                    'precio_unitario' => 10000.00,
                    'descuento' => 0,
                    'porcentaje_impuesto' => 13.00
                ]
            ]
        ];

        $response = $this->authenticatedJson('POST', '/api/ventas', $ventaData, $usuario);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'empresa_id',
                        'cliente_id',
                        'total'
                    ]
                ]);

        $this->assertDatabaseHas('ventas', [
            'cliente_id' => $cliente->id,
            'estado_venta' => 'Pendiente'  // El default que configuramos
        ]);
    }

    /** @test */
    public function test_calcula_correctamente_totales()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '2-2222-3333',
            'nombre' => 'Cliente',
            'apellidos' => 'Cálculo',
            'activo' => true,
            'eliminado' => false
        ]);

        $almacen = Almacen::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Almacén Test',
            'codigo' => 'ALM-TEST',
            'activo' => true,
            'eliminado' => false
        ]);

        $producto = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'CALC-001',
            'nombre' => 'Producto Cálculo',
            'precio_venta' => 100.00,
            'tiene_iva' => true,
            'porcentaje_impuesto' => 13.00,
            'activo' => true,
            'eliminado' => false
        ]);

        InventarioProducto::create([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad_actual' => 1000
        ]);

        $formaPago = $this->getFormaPago();

        $ventaData = [
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'usuario_id' => $usuario->id,
            'forma_pago_id' => $formaPago->id,
            'almacen_id' => $almacen->id,
            'fecha_venta' => now()->format('Y-m-d'),
            'tipo_comprobante' => 'factura',
            'tipo_pago' => 'Contado',
            'estado' => 'Completada',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 10,
                    'precio_unitario' => 100.00,
                    'descuento' => 0,
                    'porcentaje_impuesto' => 13.00
                ]
            ]
        ];

        $response = $this->authenticatedJson('POST', '/api/ventas', $ventaData, $usuario);

        $response->assertStatus(201);

        $venta = Venta::latest()->first();

        // Subtotal: 10 * 100 = 1000
        // IVA: 1000 * 0.13 = 130
        // Total: 1000 + 130 = 1130
        $this->assertEquals(1000.00, $venta->subtotal);
        $this->assertEquals(130.00, $venta->total_iva);
        $this->assertEquals(1130.00, $venta->total);
    }

    /** @test */
    public function test_puede_anular_venta()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '3-3333-4444',
            'nombre' => 'Cliente',
            'apellidos' => 'Anulación',
            'activo' => true,
            'eliminado' => false
        ]);

        $formaPago = $this->getFormaPago();

        $venta = Venta::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'forma_pago_id' => $formaPago->id,
            'numero_factura' => 'FACT-TEST-001',
            'numero_comprobante' => '00100001010000000001',
            'fecha_venta' => now(),
            'subtotal_bruto_total' => 1000.00,
            'monto_descuento_total' => 0,
            'subtotal_neto_total' => 1000.00,
            'monto_impuesto_total' => 130.00,
            'monto_total_venta' => 1130.00,
            'tipo_pago' => 'Contado',
            'tipo_comprobante' => 'factura',
            'estado_venta' => 'Completada',
            'activo' => true,
            'eliminado' => false
        ]);

        $response = $this->authenticatedJson('POST', "/api/ventas/{$venta->id}/anular", [], $usuario);

        $response->assertStatus(200);

        $venta->refresh();
        $this->assertEquals('Anulada', $venta->estado_venta);
    }

    /** @test */
    public function test_actualiza_inventario_al_vender()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '4-4444-5555',
            'nombre' => 'Cliente',
            'apellidos' => 'Inventario',
            'activo' => true,
            'eliminado' => false
        ]);

        $almacen = Almacen::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Almacén Inventario',
            'codigo' => 'ALM-INV',
            'activo' => true,
            'eliminado' => false
        ]);

        $producto = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'INV-001',
            'nombre' => 'Producto Inventario',
            'precio_venta' => 500.00,
            'tiene_iva' => false,
            'activo' => true,
            'eliminado' => false
        ]);

        $inventario = InventarioProducto::create([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad_actual' => 50
        ]);

        $cantidadInicial = $inventario->cantidad_actual;

        $formaPago = $this->getFormaPago();

        $ventaData = [
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'usuario_id' => $usuario->id,
            'forma_pago_id' => $formaPago->id,
            'almacen_id' => $almacen->id,
            'fecha_venta' => now()->format('Y-m-d'),
            'tipo_comprobante' => 'factura',
            'tipo_pago' => 'Contado',
            'estado' => 'Completada',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 5,
                    'precio_unitario' => 500.00,
                    'descuento' => 0,
                    'porcentaje_impuesto' => 0
                ]
            ]
        ];

        $response = $this->authenticatedJson('POST', '/api/ventas', $ventaData, $usuario);

        $response->assertStatus(201);

        $inventario->refresh();
        $this->assertEquals($cantidadInicial - 5, $inventario->cantidad_actual);
    }

    /** @test */
    public function test_no_permite_vender_sin_stock()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '5-5555-6666',
            'nombre' => 'Cliente',
            'apellidos' => 'Sin Stock',
            'activo' => true,
            'eliminado' => false
        ]);

        $almacen = Almacen::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Almacén Sin Stock',
            'codigo' => 'ALM-NOSTOCK',
            'activo' => true,
            'eliminado' => false
        ]);

        $producto = Producto::create([
            'empresa_id' => $empresa->id,
            'codigo' => 'NOSTOCK-001',
            'nombre' => 'Producto Sin Stock',
            'precio_venta' => 200.00,
            'tiene_iva' => false,
            'activo' => true,
            'eliminado' => false
        ]);

        InventarioProducto::create([
            'producto_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad_actual' => 2 // Solo 2 unidades
        ]);

        $formaPago = $this->getFormaPago();

        $ventaData = [
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'sucursal_id' => $sucursal->id,
            'usuario_id' => $usuario->id,
            'forma_pago_id' => $formaPago->id,
            'almacen_id' => $almacen->id,
            'fecha_venta' => now()->format('Y-m-d'),
            'tipo_comprobante' => 'factura',
            'tipo_pago' => 'Contado',
            'estado' => 'Completada',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 10, // Intentar vender más de lo disponible
                    'precio_unitario' => 200.00,
                    'descuento' => 0,
                    'porcentaje_impuesto' => 0
                ]
            ]
        ];

        $response = $this->authenticatedJson('POST', '/api/ventas', $ventaData, $usuario);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_requiere_autenticacion()
    {
        $response = $this->json('GET', '/api/ventas');

        $response->assertStatus(401);
    }

    /** @test */
    public function test_puede_listar_ventas()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $sucursal = $this->createSucursal($empresa);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '6-6666-7777',
            'nombre' => 'Cliente',
            'apellidos' => 'Lista',
            'activo' => true,
            'eliminado' => false
        ]);

        $formaPago = $this->getFormaPago();

        Venta::create([
            'empresa_id' => $empresa->id,
            'sucursal_id' => $sucursal->id,
            'cliente_id' => $cliente->id,
            'usuario_id' => $usuario->id,
            'forma_pago_id' => $formaPago->id,
            'numero_factura' => 'FACT-LIST-001',
            'numero_comprobante' => '00100001010000000002',
            'fecha_venta' => now(),
            'subtotal_bruto_total' => 1000.00,
            'monto_descuento_total' => 0,
            'subtotal_neto_total' => 1000.00,
            'monto_impuesto_total' => 0,
            'monto_total_venta' => 1000.00,
            'tipo_pago' => 'Contado',
            'tipo_comprobante' => 'factura',
            'estado_venta' => 'Completada',
            'activo' => true,
            'eliminado' => false
        ]);

        $response = $this->authenticatedJson('GET', '/api/ventas', [], $usuario);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'numero_factura',
                            'total',
                            'estado'
                        ]
                    ]
                ]);
    }
}
