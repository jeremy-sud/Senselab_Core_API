<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Tests para el trait HasCustomSoftDeletes
 * 
 * Verifica que el soft delete usando el campo 'eliminado' funciona correctamente
 */
class HasCustomSoftDeletesTest extends TestCase
{
    use RefreshDatabase;

    protected Empresa $empresa;
    protected Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear empresa para los tests
        $this->empresa = $this->createEmpresa();
        
        // Crear producto de prueba
        $this->producto = $this->createProducto([], $this->empresa);
    }

    /** @test */
    public function producto_puede_ser_eliminado_suavemente()
    {
        // El producto existe y no está eliminado
        $this->assertFalse($this->producto->eliminado);
        $this->assertFalse($this->producto->trashed());

        // Eliminar el producto (soft delete)
        $this->producto->delete();

        // Verificar que está marcado como eliminado
        $this->producto->refresh();
        $this->assertTrue($this->producto->eliminado);
        $this->assertTrue($this->producto->trashed());
    }

    /** @test */
    public function producto_eliminado_no_aparece_en_queries_normales()
    {
        $producto2 = $this->createProducto(['nombre' => 'Producto 2'], $this->empresa);
        
        // Antes de eliminar: 2 productos
        $this->assertEquals(2, Producto::count());

        // Eliminar uno
        $this->producto->delete();

        // Después de eliminar: solo 1 producto visible
        $this->assertEquals(1, Producto::count());
        
        // El producto eliminado no está en la consulta normal
        $this->assertFalse(Producto::get()->contains('id', $this->producto->id));
        
        // Pero el otro producto sí está
        $this->assertTrue(Producto::get()->contains('id', $producto2->id));
    }

    /** @test */
    public function productos_eliminados_pueden_ser_incluidos_con_withTrashed()
    {
        $producto2 = $this->createProducto(['nombre' => 'Producto 2'], $this->empresa);
        
        // Eliminar uno
        $this->producto->delete();

        // Con withTrashed() se incluyen eliminados
        $this->assertEquals(2, Producto::withTrashed()->count());
        
        // Ambos productos están en la consulta
        $productos = Producto::withTrashed()->get();
        $this->assertTrue($productos->contains('id', $this->producto->id));
        $this->assertTrue($productos->contains('id', $producto2->id));
    }

    /** @test */
    public function solo_productos_eliminados_pueden_ser_obtenidos_con_onlyTrashed()
    {
        $producto2 = $this->createProducto(['nombre' => 'Producto 2'], $this->empresa);
        $producto3 = $this->createProducto(['nombre' => 'Producto 3'], $this->empresa);
        
        // Eliminar dos productos
        $this->producto->delete();
        $producto2->delete();

        // Solo eliminados
        $eliminados = Producto::onlyTrashed()->get();
        
        $this->assertEquals(2, $eliminados->count());
        $this->assertTrue($eliminados->contains('id', $this->producto->id));
        $this->assertTrue($eliminados->contains('id', $producto2->id));
        $this->assertFalse($eliminados->contains('id', $producto3->id));
    }

    /** @test */
    public function producto_eliminado_puede_ser_restaurado()
    {
        $productoId = $this->producto->id;
        
        // Eliminar producto
        $this->producto->delete();
        $this->assertTrue($this->producto->trashed());

        // Restaurar
        $this->producto->restore();
        
        // Verificar que ya no está eliminado usando instancia fresca
        $productoRestaurado = Producto::find($productoId);
        $this->assertFalse($productoRestaurado->eliminado);
        $this->assertFalse($productoRestaurado->trashed());
        
        // Aparece en queries normales
        $this->assertTrue(Producto::get()->contains('id', $productoId));
    }

    /** @test */
    public function forceDelete_elimina_producto_permanentemente()
    {
        $productoId = $this->producto->id;
        
        // Soft delete primero
        $this->producto->delete();
        
        // Verificar que existe en la BD (con withTrashed)
        $this->assertNotNull(Producto::withTrashed()->find($productoId));
        
        // Force delete
        $this->producto->forceDelete();
        
        // Ahora NO existe en la BD
        $this->assertNull(Producto::withTrashed()->find($productoId));
    }

    /** @test */
    public function trashed_method_retorna_estado_correcto()
    {
        $productoId = $this->producto->id;
        
        // No eliminado
        $this->assertFalse($this->producto->trashed());
        
        // Eliminar
        $this->producto->delete();
        
        // Verificar en instancia fresca
        $productoEliminado = Producto::withTrashed()->find($productoId);
        $this->assertTrue($productoEliminado->trashed());
        
        // Restaurar
        $productoEliminado->restore();
        
        // Verificar en instancia fresca
        $productoRestaurado = Producto::find($productoId);
        $this->assertFalse($productoRestaurado->trashed());
    }

    /** @test */
    public function trait_funciona_en_diferentes_modelos()
    {
        // Crear cliente
        $cliente = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '1-1234-5678',
            'nombre' => 'Cliente',
            'apellidos' => 'Test',
            'email' => 'cliente@test.com',
            'activo' => true,
            'eliminado' => false,
        ]);
        
        $clienteId = $cliente->id;

        // Verificar soft delete funciona
        $this->assertFalse($cliente->trashed());
        $cliente->delete();
        
        // Verificar en instancia fresca
        $clienteEliminado = Cliente::withTrashed()->find($clienteId);
        $this->assertTrue($clienteEliminado->trashed());
        
        // No aparece en queries normales
        $this->assertFalse(Cliente::get()->contains('id', $clienteId));
        
        // Pero sí con withTrashed
        $this->assertTrue(Cliente::withTrashed()->get()->contains('id', $clienteId));
        
        // Puede ser restaurado
        $clienteEliminado->restore();
        $clienteRestaurado = Cliente::find($clienteId);
        $this->assertFalse($clienteRestaurado->trashed());
    }

    /** @test */
    public function eliminar_dos_veces_no_causa_error()
    {
        // Primera eliminación
        $this->producto->delete();
        $this->assertTrue($this->producto->trashed());
        
        // Segunda eliminación (no debería causar error)
        $this->producto->delete();
        $this->assertTrue($this->producto->trashed());
    }

    /** @test */
    public function restaurar_producto_no_eliminado_no_causa_error()
    {
        // Producto no eliminado
        $this->assertFalse($this->producto->trashed());
        
        // Intentar restaurar (no debería causar error)
        $this->producto->restore();
        
        // Sigue sin estar eliminado
        $this->assertFalse($this->producto->trashed());
    }

    /** @test */
    public function global_scope_filtra_eliminados_automaticamente()
    {
        $producto2 = $this->createProducto(['nombre' => 'Producto 2'], $this->empresa);
        $producto3 = $this->createProducto(['nombre' => 'Producto 3'], $this->empresa);
        
        // Marcar uno como eliminado directamente en BD (sin usar delete())
        Producto::withoutGlobalScopes()->where('id', $producto2->id)
            ->update(['eliminado' => true]);
        
        // El scope debe filtrarlo automáticamente
        $productos = Producto::all();
        
        $this->assertEquals(2, $productos->count());
        $this->assertTrue($productos->contains('id', $this->producto->id));
        $this->assertFalse($productos->contains('id', $producto2->id));
        $this->assertTrue($productos->contains('id', $producto3->id));
    }

    /** @test */
    public function puede_contar_productos_eliminados()
    {
        $producto2 = $this->createProducto(['nombre' => 'Producto 2'], $this->empresa);
        $producto3 = $this->createProducto(['nombre' => 'Producto 3'], $this->empresa);
        
        // Eliminar dos
        $this->producto->delete();
        $producto2->delete();
        
        // Contar
        $this->assertEquals(1, Producto::count());
        $this->assertEquals(2, Producto::onlyTrashed()->count());
        $this->assertEquals(3, Producto::withTrashed()->count());
    }

    /** @test */
    public function where_clauses_funcionan_con_soft_deletes()
    {
        $producto2 = $this->createProducto([
            'nombre' => 'Producto Especial',
            'precio_venta' => 5000
        ], $this->empresa);
        
        $producto3 = $this->createProducto([
            'nombre' => 'Otro Producto',
            'precio_venta' => 5000
        ], $this->empresa);
        
        // Eliminar uno
        $producto2->delete();
        
        // Query con where
        $productos = Producto::where('precio_venta', 5000)->get();
        
        // Solo debe retornar el no eliminado
        $this->assertEquals(1, $productos->count());
        $this->assertTrue($productos->contains('id', $producto3->id));
        $this->assertFalse($productos->contains('id', $producto2->id));
    }
}
