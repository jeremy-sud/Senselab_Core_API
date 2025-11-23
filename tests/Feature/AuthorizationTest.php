<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Producto;
use App\Models\Cliente;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests de Autorización - Sistema de Policies
 * 
 * Verifica que las policies implementadas funcionen correctamente:
 * - Multi-tenancy: Usuarios no pueden acceder a recursos de otras empresas
 * - RBAC: Usuarios sin permisos reciben 403
 * - Permisos correctos: Usuarios con permisos pueden acceder
 * 
 * Cobertura: 16 policies implementadas
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa1;
    private Empresa $empresa2;
    private Usuario $usuario1;
    private Usuario $usuario2;
    private Rol $rolAdmin;
    private Rol $rolUsuario;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear 2 empresas para testing multi-tenant (sin factory para evitar FK issues)
        $this->empresa1 = Empresa::create([
            'nombre' => 'Empresa Test 1',
            'nombre_comercial' => 'Empresa Test 1',
            'razon_social' => 'Empresa Test 1 S.A.',
            'num_identificacion_dgt' => '1234567890',
            'tipo_identificacion' => '02',
            'email' => 'empresa1@test.com',
            'activo' => true,
            'eliminado' => false,
        ]);
        
        $this->empresa2 = Empresa::create([
            'nombre' => 'Empresa Test 2',
            'nombre_comercial' => 'Empresa Test 2',
            'razon_social' => 'Empresa Test 2 S.A.',
            'num_identificacion_dgt' => '0987654321',
            'tipo_identificacion' => '02',
            'email' => 'empresa2@test.com',
            'activo' => true,
            'eliminado' => false,
        ]);

        // Crear roles con permisos
        $this->rolAdmin = Rol::create([
            'empresa_id' => $this->empresa1->id,
            'nombre' => 'Administrador',
            'descripcion' => 'Rol de administrador con todos los permisos',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->rolUsuario = Rol::create([
            'empresa_id' => $this->empresa1->id,
            'nombre' => 'Usuario Limitado',
            'descripcion' => 'Usuario con permisos limitados',
            'activo' => true,
            'eliminado' => false,
        ]);

        // Crear permisos para Producto
        $permisoVerProductos = Permiso::create([
            'nombre' => 'producto.leer',
            'slug' => 'producto-leer',
            'descripcion' => 'Ver productos',
        ]);

        $permisoCrearProductos = Permiso::create([
            'nombre' => 'producto.crear',
            'slug' => 'producto-crear',
            'descripcion' => 'Crear productos',
        ]);

        $permisoActualizarProductos = Permiso::create([
            'nombre' => 'producto.actualizar',
            'slug' => 'producto-actualizar',
            'descripcion' => 'Actualizar productos',
        ]);

        // Asignar todos los permisos al rol admin
        $this->rolAdmin->permisos()->attach([
            $permisoVerProductos->id,
            $permisoCrearProductos->id,
            $permisoActualizarProductos->id,
        ]);

        // Asignar solo permiso de lectura al rol usuario
        $this->rolUsuario->permisos()->attach($permisoVerProductos->id);

        // Crear usuarios de prueba
        $this->usuario1 = Usuario::create([
            'empresa_id' => $this->empresa1->id,
            'email' => 'admin@empresa1.com',
            'nombre' => 'Admin',
            'apellidos' => 'Test',
            'password_hash' => bcrypt('password'),
            'rol_id' => $this->rolAdmin->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->usuario2 = Usuario::create([
            'empresa_id' => $this->empresa2->id,
            'email' => 'user@empresa2.com',
            'nombre' => 'User',
            'apellidos' => 'Test',
            'password_hash' => bcrypt('password'),
            'rol_id' => null, // Sin rol
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    /**
     * Test 1: Usuario no puede ver recursos de otra empresa (Multi-tenancy)
     */
    public function test_usuario_no_puede_ver_recursos_de_otra_empresa(): void
    {
        // Crear producto en empresa 1
        $producto = Producto::create([
            'empresa_id' => $this->empresa1->id,
            'nombre' => 'Producto Empresa 1',
            'codigo' => 'PROD-001',
            'precio_venta' => 1000,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Autenticar como usuario de empresa 2
        Sanctum::actingAs($this->usuario2);

        // Intentar acceder al producto de empresa 1
        $response = $this->getJson("/api/productos/{$producto->id}");

        // Debe retornar 403 Forbidden (policy verifica empresa_id)
        $response->assertStatus(403);
    }

    /**
     * Test 2: Usuario sin permiso recibe 403
     */
    public function test_usuario_sin_permiso_recibe_403(): void
    {
        // Crear usuario sin permisos
        $usuarioSinPermisos = Usuario::create([
            'empresa_id' => $this->empresa1->id,
            'email' => 'sinpermisos@empresa1.com',
            'nombre' => 'Sin Permisos',
            'apellidos' => 'Test',
            'password_hash' => bcrypt('password'),
            'rol_id' => $this->rolUsuario->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Autenticar
        Sanctum::actingAs($usuarioSinPermisos);

        // Intentar crear producto (no tiene permiso producto.crear)
        $response = $this->postJson('/api/productos', [
            'empresa_id' => $this->empresa1->id,
            'nombre' => 'Producto Test',
            'precio' => 1000,
        ]);

        // Debe retornar 403 Forbidden
        $response->assertStatus(403);
    }

    /**
     * Test 3: Usuario con permiso correcto puede acceder
     */
    public function test_usuario_con_permiso_correcto_puede_acceder(): void
    {
        // Autenticar como admin (tiene todos los permisos)
        Sanctum::actingAs($this->usuario1);

        // Crear producto (tiene permiso producto.crear)
        $response = $this->postJson('/api/productos', [
            'empresa_id' => $this->empresa1->id,
            'codigo' => 'PROD-001',
            'nombre' => 'Producto Test Autorizado',
            'precio_venta' => 1500,
            'categoria_producto_id' => null,
        ]);

        // Debe retornar 201 Created
        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'nombre',
                'precio_venta',
            ]
        ]);
    }

    /**
     * Test 4: Multi-tenancy funciona correctamente en listados
     */
    public function test_multi_tenancy_funciona_en_listados(): void
    {
        // Crear productos en ambas empresas
        for ($i = 1; $i <= 5; $i++) {
            Producto::create([
                'empresa_id' => $this->empresa1->id,
                'nombre' => "Producto E1-{$i}",
                'codigo' => "PE1-{$i}",
                'precio_venta' => 1000 * $i,
                'activo' => true,
                'eliminado' => false,
            ]);
        }
        
        for ($i = 1; $i <= 3; $i++) {
            Producto::create([
                'empresa_id' => $this->empresa2->id,
                'nombre' => "Producto E2-{$i}",
                'codigo' => "PE2-{$i}",
                'precio_venta' => 2000 * $i,
                'activo' => true,
                'eliminado' => false,
            ]);
        }

        // Autenticar como usuario de empresa 1
        Sanctum::actingAs($this->usuario1);

        // Obtener listado de productos
        $response = $this->getJson('/api/productos');

        $response->assertStatus(200);
        
        // Debe retornar solo productos de empresa 1 (5 productos)
        $data = $response->json('data');
        $this->assertCount(5, $data);

        // Verificar que todos sean de empresa 1
        foreach ($data as $producto) {
            $this->assertEquals($this->empresa1->id, $producto['empresa_id']);
        }
    }

    /**
     * Test 5: RBAC verifica permisos granulares correctamente
     */
    public function test_rbac_verifica_permisos_granulares(): void
    {
        // Crear usuario con rol limitado (solo lectura)
        $usuarioLectora = Usuario::create([
            'empresa_id' => $this->empresa1->id,
            'email' => 'lector@empresa1.com',
            'nombre' => 'Lector',
            'apellidos' => 'Test',
            'password_hash' => bcrypt('password'),
            'rol_id' => $this->rolUsuario->id, // Solo tiene producto.leer
            'activo' => true,
            'eliminado' => false,
        ]);

        // Crear producto
        $producto = Producto::create([
            'empresa_id' => $this->empresa1->id,
            'nombre' => 'Producto Test',
            'codigo' => 'PROD-TEST',
            'precio_venta' => 1500,
            'activo' => true,
            'eliminado' => false,
        ]);

        Sanctum::actingAs($usuarioLectora);

        // PUEDE ver productos (tiene producto.leer)
        $response = $this->getJson('/api/productos');
        $response->assertStatus(200);

        $response = $this->getJson("/api/productos/{$producto->id}");
        $response->assertStatus(200);

        // NO PUEDE actualizar productos (no tiene producto.actualizar)
        $response = $this->putJson("/api/productos/{$producto->id}", [
            'nombre' => 'Producto Modificado',
        ]);
        $response->assertStatus(403);

        // NO PUEDE eliminar productos (no tiene producto.eliminar)
        $response = $this->deleteJson("/api/productos/{$producto->id}");
        $response->assertStatus(403);
    }

    /**
     * Test 6: Policy verifica recurso eliminado
     */
    public function test_policy_verifica_recurso_no_eliminado(): void
    {
        // Crear cliente
        $cliente = Cliente::create([
            'empresa_id' => $this->empresa1->id,
            'nombre' => 'Cliente Test',
            'numero_identificacion' => '123456789',
            'activo' => true,
            'eliminado' => false,
        ]);

        Sanctum::actingAs($this->usuario1);

        // Puede acceder a cliente activo
        $response = $this->getJson("/api/clientes/{$cliente->id}");
        $response->assertStatus(200);

        // Marcar como eliminado
        $cliente->update(['eliminado' => true]);

        // No debe poder acceder a cliente eliminado
        $response = $this->getJson("/api/clientes/{$cliente->id}");
        $response->assertStatus(404); // findOrFail no lo encuentra
    }

    /**
     * Test 7: Policies funcionan para múltiples recursos
     */
    public function test_policies_funcionan_para_multiples_recursos(): void
    {
        Sanctum::actingAs($this->usuario1);

        // Crear permisos para otros recursos
        $permisoCrearCliente = Permiso::create([
            'nombre' => 'cliente.crear',
            'slug' => 'cliente-crear',
            'descripcion' => 'Crear clientes',
        ]);
        $permisoCrearVenta = Permiso::create([
            'nombre' => 'venta.crear',
            'slug' => 'venta-crear',
            'descripcion' => 'Crear ventas',
        ]);
        $this->rolAdmin->permisos()->attach([$permisoCrearCliente->id, $permisoCrearVenta->id]);

        // Verificar Cliente
        $responseCliente = $this->postJson('/api/clientes', [
            'empresa_id' => $this->empresa1->id,
            'nombre' => 'Cliente Test',
            'numero_identificacion' => '123456789',
        ]);
        $responseCliente->assertStatus(201);

        // Verificar que productos de la empresa 1 estén accesibles
        $response = $this->getJson('/api/productos');
        $response->assertStatus(200);
    }

    /**
     * Test 8: Usuario sin autenticar recibe 401
     */
    public function test_usuario_sin_autenticar_recibe_401(): void
    {
        // Sin autenticar
        $response = $this->getJson('/api/productos');
        
        // Debe retornar 401 Unauthorized
        $response->assertStatus(401);
    }
}
