<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
/**
 * Tests para el trait HasActiveScope
 * 
 * Verifica que los scopes y helpers de activo/inactivo funcionan correctamente
 */
class HasActiveScopeTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear empresa para los tests
        $this->empresa = $this->createEmpresa();
    }

    #[Test]
    public function scope_activo_retorna_solo_productos_activos()
    {
        // Crear productos activos e inactivos
        $productoActivo1 = $this->createProducto(['nombre' => 'Activo 1', 'activo' => true], $this->empresa);
        $productoActivo2 = $this->createProducto(['nombre' => 'Activo 2', 'activo' => true], $this->empresa);
        $productoInactivo = $this->createProducto(['nombre' => 'Inactivo', 'activo' => false], $this->empresa);
        
        // Usar scope activo()
        $productosActivos = Producto::activo()->get();
        
        // Solo debe retornar los activos
        $this->assertEquals(2, $productosActivos->count());
        $this->assertTrue($productosActivos->contains('id', $productoActivo1->id));
        $this->assertTrue($productosActivos->contains('id', $productoActivo2->id));
        $this->assertFalse($productosActivos->contains('id', $productoInactivo->id));
    }

    #[Test]
    public function scope_inactivo_retorna_solo_productos_inactivos()
    {
        // Crear productos activos e inactivos
        $productoActivo = $this->createProducto(['nombre' => 'Activo', 'activo' => true], $this->empresa);
        $productoInactivo1 = $this->createProducto(['nombre' => 'Inactivo 1', 'activo' => false], $this->empresa);
        $productoInactivo2 = $this->createProducto(['nombre' => 'Inactivo 2', 'activo' => false], $this->empresa);
        
        // Usar scope inactivo()
        $productosInactivos = Producto::inactivo()->get();
        
        // Solo debe retornar los inactivos
        $this->assertEquals(2, $productosInactivos->count());
        $this->assertTrue($productosInactivos->contains('id', $productoInactivo1->id));
        $this->assertTrue($productosInactivos->contains('id', $productoInactivo2->id));
        $this->assertFalse($productosInactivos->contains('id', $productoActivo->id));
    }

    #[Test]
    public function scope_conInactivos_retorna_todos_los_productos()
    {
        // Crear productos activos e inactivos
        $productoActivo = $this->createProducto(['nombre' => 'Activo', 'activo' => true], $this->empresa);
        $productoInactivo = $this->createProducto(['nombre' => 'Inactivo', 'activo' => false], $this->empresa);
        
        // Usar scope conInactivos()
        $todosLosProductos = Producto::conInactivos()->get();
        
        // Debe retornar ambos
        $this->assertEquals(2, $todosLosProductos->count());
        $this->assertTrue($todosLosProductos->contains('id', $productoActivo->id));
        $this->assertTrue($todosLosProductos->contains('id', $productoInactivo->id));
    }

    #[Test]
    public function estaActivo_retorna_estado_correcto()
    {
        // Producto activo
        $productoActivo = $this->createProducto(['activo' => true], $this->empresa);
        $this->assertTrue($productoActivo->estaActivo());
        
        // Producto inactivo
        $productoInactivo = $this->createProducto(['activo' => false], $this->empresa);
        $this->assertFalse($productoInactivo->estaActivo());
    }

    #[Test]
    public function activar_cambia_producto_a_activo()
    {
        // Crear producto inactivo
        $producto = $this->createProducto(['activo' => false], $this->empresa);
        $this->assertFalse($producto->activo);
        
        // Activar
        $producto->activar();
        
        // Verificar
        $producto->refresh();
        $this->assertTrue($producto->activo);
        $this->assertTrue($producto->estaActivo());
    }

    #[Test]
    public function desactivar_cambia_producto_a_inactivo()
    {
        // Crear producto activo
        $producto = $this->createProducto(['activo' => true], $this->empresa);
        $this->assertTrue($producto->activo);
        
        // Desactivar
        $producto->desactivar();
        
        // Verificar
        $producto->refresh();
        $this->assertFalse($producto->activo);
        $this->assertFalse($producto->estaActivo());
    }

    #[Test]
    public function toggleActivo_alterna_estado()
    {
        // Crear producto activo
        $producto = $this->createProducto(['activo' => true], $this->empresa);
        
        // Toggle: activo -> inactivo
        $producto->toggleActivo();
        $producto->refresh();
        $this->assertFalse($producto->activo);
        
        // Toggle: inactivo -> activo
        $producto->toggleActivo();
        $producto->refresh();
        $this->assertTrue($producto->activo);
    }

    #[Test]
    public function scopes_pueden_combinarse_con_where()
    {
        // Crear productos
        $producto1 = $this->createProducto([
            'nombre' => 'Producto Premium',
            'precio_venta' => 5000,
            'activo' => true
        ], $this->empresa);
        
        $producto2 = $this->createProducto([
            'nombre' => 'Producto Básico',
            'precio_venta' => 1000,
            'activo' => true
        ], $this->empresa);
        
        $producto3 = $this->createProducto([
            'nombre' => 'Producto Premium Inactivo',
            'precio_venta' => 5000,
            'activo' => false
        ], $this->empresa);
        
        // Combinar scope con where
        $productosPremiumActivos = Producto::activo()
            ->where('precio_venta', '>', 3000)
            ->get();
        
        $this->assertEquals(1, $productosPremiumActivos->count());
        $this->assertTrue($productosPremiumActivos->contains('id', $producto1->id));
    }

    #[Test]
    public function scopes_pueden_combinarse_con_orderBy()
    {
        // Crear productos con diferentes precios
        $producto1 = $this->createProducto([
            'nombre' => 'Producto A',
            'precio_venta' => 3000,
            'activo' => true
        ], $this->empresa);
        
        $producto2 = $this->createProducto([
            'nombre' => 'Producto B',
            'precio_venta' => 1000,
            'activo' => true
        ], $this->empresa);
        
        $producto3 = $this->createProducto([
            'nombre' => 'Producto C',
            'precio_venta' => 2000,
            'activo' => true
        ], $this->empresa);
        
        // Scope + orderBy
        $productos = Producto::activo()
            ->orderBy('precio_venta', 'desc')
            ->get();
        
        $this->assertEquals($producto1->id, $productos->first()->id);
        $this->assertEquals($producto2->id, $productos->last()->id);
    }

    #[Test]
    public function scopes_funcionan_con_count()
    {
        // Crear 3 activos y 2 inactivos
        $this->createProducto(['activo' => true], $this->empresa);
        $this->createProducto(['activo' => true], $this->empresa);
        $this->createProducto(['activo' => true], $this->empresa);
        $this->createProducto(['activo' => false], $this->empresa);
        $this->createProducto(['activo' => false], $this->empresa);
        
        // Contar
        $this->assertEquals(3, Producto::activo()->count());
        $this->assertEquals(2, Producto::inactivo()->count());
        $this->assertEquals(5, Producto::conInactivos()->count());
    }

    #[Test]
    public function scopes_funcionan_con_first()
    {
        // Crear productos
        $productoActivo = $this->createProducto(['nombre' => 'Primer Activo', 'activo' => true], $this->empresa);
        $this->createProducto(['nombre' => 'Inactivo', 'activo' => false], $this->empresa);
        
        // First con scope
        $primerActivo = Producto::activo()->orderBy('id')->first();
        
        $this->assertEquals($productoActivo->id, $primerActivo->id);
    }

    #[Test]
    public function trait_funciona_en_diferentes_modelos()
    {
        // Probar con Cliente
        $clienteActivo = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '1-1234-5678',
            'nombre' => 'Cliente Activo',
            'apellidos' => 'Test',
            'email' => 'activo@test.com',
            'activo' => true,
            'eliminado' => false,
        ]);
        
        $clienteInactivo = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '1-8765-4321',
            'nombre' => 'Cliente Inactivo',
            'apellidos' => 'Test',
            'email' => 'inactivo@test.com',
            'activo' => false,
            'eliminado' => false,
        ]);
        
        // Scopes funcionan
        $this->assertEquals(1, Cliente::activo()->count());
        $this->assertEquals(1, Cliente::inactivo()->count());
        
        // Helpers funcionan
        $this->assertTrue($clienteActivo->estaActivo());
        $this->assertFalse($clienteInactivo->estaActivo());
        
        // Toggle funciona
        $clienteActivo->toggleActivo();
        $clienteActivo->refresh();
        $this->assertFalse($clienteActivo->estaActivo());
    }

    #[Test]
    public function activar_producto_ya_activo_no_causa_error()
    {
        // Producto activo
        $producto = $this->createProducto(['activo' => true], $this->empresa);
        
        // Activar de nuevo (no debería causar error)
        $producto->activar();
        
        // Sigue activo
        $producto->refresh();
        $this->assertTrue($producto->activo);
    }

    #[Test]
    public function desactivar_producto_ya_inactivo_no_causa_error()
    {
        // Producto inactivo
        $producto = $this->createProducto(['activo' => false], $this->empresa);
        
        // Desactivar de nuevo (no debería causar error)
        $producto->desactivar();
        
        // Sigue inactivo
        $producto->refresh();
        $this->assertFalse($producto->activo);
    }

    #[Test]
    public function scope_activo_excluye_eliminados()
    {
        // Crear producto activo y eliminado
        $productoActivo = $this->createProducto([
            'activo' => true,
            'eliminado' => false
        ], $this->empresa);
        
        $productoActivoEliminado = $this->createProducto([
            'activo' => true,
            'eliminado' => true
        ], $this->empresa);
        
        // Scope activo() debe excluir eliminados (por global scope)
        $productosActivos = Producto::activo()->get();
        
        $this->assertEquals(1, $productosActivos->count());
        $this->assertTrue($productosActivos->contains('id', $productoActivo->id));
        $this->assertFalse($productosActivos->contains('id', $productoActivoEliminado->id));
    }

    #[Test]
    public function scopes_funcionan_con_pagination()
    {
        // Crear 10 productos activos
        for ($i = 1; $i <= 10; $i++) {
            $this->createProducto(['nombre' => "Producto {$i}", 'activo' => true], $this->empresa);
        }
        
        // Crear 5 productos inactivos
        for ($i = 1; $i <= 5; $i++) {
            $this->createProducto(['nombre' => "Inactivo {$i}", 'activo' => false], $this->empresa);
        }
        
        // Paginar solo activos
        $productosActivos = Producto::activo()->paginate(5);
        
        $this->assertEquals(10, $productosActivos->total());
        $this->assertEquals(5, $productosActivos->count());
        $this->assertEquals(2, $productosActivos->lastPage());
    }

    #[Test]
    public function multiples_toggles_alternan_correctamente()
    {
        $producto = $this->createProducto(['activo' => true], $this->empresa);
        
        // Toggle 1: activo -> inactivo
        $producto->toggleActivo();
        $producto->refresh();
        $this->assertFalse($producto->activo);
        
        // Toggle 2: inactivo -> activo
        $producto->toggleActivo();
        $producto->refresh();
        $this->assertTrue($producto->activo);
        
        // Toggle 3: activo -> inactivo
        $producto->toggleActivo();
        $producto->refresh();
        $this->assertFalse($producto->activo);
    }

    #[Test]
    public function scope_activo_funciona_con_relaciones()
    {
        // Crear productos activos e inactivos
        $productoActivo = $this->createProducto(['activo' => true], $this->empresa);
        $productoInactivo = $this->createProducto(['activo' => false], $this->empresa);
        
        // Query con relación
        $empresa = Empresa::with(['productos' => function ($query) {
            $query->activo();
        }])->find($this->empresa->id);
        
        // Solo debe cargar productos activos
        $this->assertEquals(1, $empresa->productos->count());
        $this->assertTrue($empresa->productos->contains('id', $productoActivo->id));
    }

    #[Test]
    public function scope_puede_combinarse_con_otros_scopes()
    {
        // Crear productos
        $producto1 = $this->createProducto([
            'nombre' => 'Producto Activo No Eliminado',
            'activo' => true,
            'eliminado' => false
        ], $this->empresa);
        
        $producto2 = $this->createProducto([
            'nombre' => 'Producto Activo Eliminado',
            'activo' => true,
            'eliminado' => false
        ], $this->empresa);
        $producto2->update(['eliminado' => true]); // Marcar como eliminado manualmente
        
        $producto3 = $this->createProducto([
            'nombre' => 'Producto Inactivo',
            'activo' => false,
            'eliminado' => false
        ], $this->empresa);
        
        // Combinar scopes - activo() solo debería retornar productos activos no eliminados
        $resultado = Producto::activo()->get();
        
        // Solo debe incluir producto1 (activo y no eliminado)
        $this->assertEquals(1, $resultado->count());
        $this->assertEquals($producto1->id, $resultado->first()->id);
    }
}
