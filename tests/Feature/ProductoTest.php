<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Producto;
use App\Models\CategoriaProducto;
use App\Models\UnidadMedida;
use App\Models\Marca;
use App\Models\TipoImpuesto;

class ProductoTest extends TestCase
{
    /**
     * Test: Listar productos con autenticación
     */
    public function test_puede_listar_productos_autenticado(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $this->createProducto(['nombre' => 'Producto Test 1', 'codigo' => 'PROD001'], $empresa);
        $this->createProducto(['nombre' => 'Producto Test 2', 'codigo' => 'PROD002'], $empresa);

        // Act
        $response = $this->authenticatedJson('GET', '/api/productos', [], $usuario);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nombre',
                        'codigo',
                        'precio_venta',
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test: Crear producto con datos válidos
     */
    public function test_puede_crear_producto_con_datos_validos(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $productoData = [
            'empresa_id' => $empresa->id,
            'categoria_id' => $this->getCategoriaProducto($empresa)->id,
            'unidad_medida_id' => $this->getUnidadMedida()->id,
            'nombre' => 'Nuevo Producto',
            'codigo' => 'NP001',
            'tipo' => 'producto',
            'descripcion' => 'Descripción del producto',
            'precio_venta' => 5000.00,
            'precio_compra' => 3000.00,
            'activo' => true,
        ];

        // Act
        $response = $this->authenticatedJson('POST', '/api/productos', $productoData, $usuario);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'nombre',
                    'codigo',
                    'precio_venta',
                ],
            ]);

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Nuevo Producto',
            'codigo' => 'NP001',
        ]);
    }

    /**
     * Test: No puede crear producto sin autenticación
     */
    public function test_no_puede_crear_producto_sin_autenticacion(): void
    {
        // Arrange
        $productoData = [
            'nombre' => 'Producto Sin Auth',
            'codigo' => 'PSA001',
            'precio_venta' => 1000.00,
        ];

        // Act
        $response = $this->postJson('/api/productos', $productoData);

        // Assert
        $response->assertStatus(401);
    }

    /**
     * Test: Validación de campos requeridos al crear producto
     */
    public function test_validacion_campos_requeridos_al_crear_producto(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();

        // Act: Crear sin campos requeridos
        $response = $this->authenticatedJson('POST', '/api/productos', [], $usuario);

        // Assert: Debe fallar por falta de campos requeridos
        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'empresa_id',
                'categoria_id',
                'unidad_medida_id',
                'nombre',
                'tipo',
                'precio_venta'
            ]);
    }

    /**
     * Test: No puede crear producto con código duplicado en la misma empresa
     */
    public function test_no_puede_crear_producto_con_codigo_duplicado(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $this->createProducto(['nombre' => 'Producto Original', 'codigo' => 'DUP001'], $empresa);

        // Act: Intentar crear con mismo código
        $response = $this->authenticatedJson('POST', '/api/productos', [
            'empresa_id' => $empresa->id,
            'categoria_id' => $this->getCategoriaProducto($empresa)->id,
            'unidad_medida_id' => $this->getUnidadMedida()->id,
            'nombre' => 'Producto Duplicado',
            'codigo' => 'DUP001',
            'tipo' => 'producto',
            'precio_venta' => 2000.00,
        ], $usuario);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['codigo']);
    }

    /**
     * Test: Puede actualizar un producto existente
     */
    public function test_puede_actualizar_producto_existente(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $producto = $this->createProducto([
            'empresa_id' => $empresa->id,
            'nombre' => 'Producto Original',
            'codigo' => 'ORIG001',
            'precio_venta' => 1000.00,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Act
        $response = $this->authenticatedJson('PUT', "/api/productos/{$producto->id}", [
            'nombre' => 'Producto Actualizado',
            'precio_venta' => 1500.00,
        ], $usuario);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'nombre' => 'Producto Actualizado',
                    'precio_venta' => 1500.00,
                ],
            ]);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'nombre' => 'Producto Actualizado',
        ]);
    }

    /**
     * Test: Puede eliminar un producto (soft delete)
     */
    public function test_puede_eliminar_producto_soft_delete(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $producto = $this->createProducto([
            'empresa_id' => $empresa->id,
            'nombre' => 'Producto a Eliminar',
            'codigo' => 'DEL001',
            'precio_venta' => 1000.00,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Act
        $response = $this->authenticatedJson('DELETE', "/api/productos/{$producto->id}", [], $usuario);

        // Assert
        $response->assertStatus(200);

        $this->assertDatabaseHas('productos', [
            'id' => $producto->id,
            'eliminado' => true,
        ]);
    }

    /**
     * Test: Búsqueda de productos por nombre
     */
    public function test_puede_buscar_productos_por_nombre(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $this->createProducto([
            'empresa_id' => $empresa->id,
            'nombre' => 'Laptop Dell',
            'codigo' => 'LAP001',
            'precio_venta' => 5000.00,
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->createProducto([
            'empresa_id' => $empresa->id,
            'nombre' => 'Mouse Logitech',
            'codigo' => 'MOU001',
            'precio_venta' => 500.00,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Act
        $response = $this->authenticatedJson('GET', '/api/productos?search=Laptop', [], $usuario);

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('Laptop Dell', $data[0]['nombre']);
    }

    /**
     * Test: Filtrar productos por estado activo
     */
    public function test_puede_filtrar_productos_por_estado_activo(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $this->createProducto([
            'empresa_id' => $empresa->id,
            'nombre' => 'Producto Activo',
            'codigo' => 'ACT001',
            'precio_venta' => 1000.00,
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->createProducto([
            'empresa_id' => $empresa->id,
            'nombre' => 'Producto Inactivo',
            'codigo' => 'INA001',
            'precio_venta' => 1000.00,
            'activo' => false,
            'eliminado' => false,
        ]);

        // Act
        $response = $this->authenticatedJson('GET', '/api/productos?activo=1', [], $usuario);

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('Producto Activo', $data[0]['nombre']);
    }

    /**
     * Test: Paginación de productos
     */
    public function test_productos_estan_paginados(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        // Crear 25 productos
        for ($i = 1; $i <= 25; $i++) {
            $this->createProducto([
                'empresa_id' => $empresa->id,
                'nombre' => "Producto {$i}",
                'codigo' => "PROD" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'precio_venta' => 1000.00 * $i,
                'activo' => true,
                'eliminado' => false,
            ]);
        }

        // Act
        $response = $this->authenticatedJson('GET', '/api/productos?per_page=10', [], $usuario);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);

        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    /**
     * Test: Solo puede ver productos de su empresa
     */
    public function test_usuario_solo_ve_productos_de_su_empresa(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario1 = $this->createUsuario(['email' => 'user1@test.com'], ['Administrador']);
        $empresa1 = $usuario1->empresa;

        $empresa2 = $this->createEmpresa([
            'nombre' => 'Otra Empresa',
            'email' => 'empresa2@test.com',
        ]);
        $usuario2 = $this->createUsuario([
            'email' => 'user2@test.com',
            'empresa_id' => $empresa2->id,
        ], ['Administrador']);

        // Crear productos para empresa1
        $this->createProducto([
            'empresa_id' => $empresa1->id,
            'nombre' => 'Producto Empresa 1',
            'codigo' => 'EMP1-001',
            'precio_venta' => 1000.00,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Crear productos para empresa2
        $this->createProducto([
            'empresa_id' => $empresa2->id,
            'nombre' => 'Producto Empresa 2',
            'codigo' => 'EMP2-001',
            'precio_venta' => 2000.00,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Act: Usuario1 consulta productos
        $response = $this->authenticatedJson('GET', '/api/productos', [], $usuario1);

        // Assert: Solo ve producto de su empresa
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('Producto Empresa 1', $data[0]['nombre']);
    }

    /**
     * Test: Productos eliminados no aparecen en listado por defecto
     */
    public function test_productos_eliminados_no_aparecen_en_listado(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $this->createProducto([
            'empresa_id' => $empresa->id,
            'nombre' => 'Producto Activo',
            'codigo' => 'ACT001',
            'precio_venta' => 1000.00,
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->createProducto([
            'empresa_id' => $empresa->id,
            'nombre' => 'Producto Eliminado',
            'codigo' => 'DEL001',
            'precio_venta' => 1000.00,
            'activo' => true,
            'eliminado' => true,
        ]);

        // Act
        $response = $this->authenticatedJson('GET', '/api/productos', [], $usuario);

        // Assert
        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertCount(1, $data);
        $this->assertEquals('Producto Activo', $data[0]['nombre']);
    }
}
