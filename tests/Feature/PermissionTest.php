<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;

class PermissionTest extends TestCase
{
    /**
     * Test: Usuario con rol Administrador tiene todos los permisos
     */
    public function test_usuario_administrador_tiene_todos_los_permisos(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        $rolAdmin = Rol::where('nombre', 'Administrador')->first();
        $this->assignAllPermissionsToRole($rolAdmin);
        
        $usuario = $this->createUsuario([], ['Administrador']);

        // Act & Assert
        $this->assertTrue($usuario->hasPermission('ver-productos'));
        $this->assertTrue($usuario->hasPermission('crear-productos'));
        $this->assertTrue($usuario->hasPermission('editar-productos'));
        $this->assertTrue($usuario->hasPermission('eliminar-productos'));
    }

    /**
     * Test: Usuario sin rol no tiene permisos
     */
    public function test_usuario_sin_rol_no_tiene_permisos(): void
    {
        // Arrange
        $this->seedPermisos();
        $usuario = $this->createUsuario(); // Sin roles

        // Act & Assert
        $this->assertFalse($usuario->hasPermission('ver-productos'));
        $this->assertFalse($usuario->hasPermission('crear-productos'));
    }

    /**
     * Test: Usuario con rol específico solo tiene permisos de ese rol
     */
    public function test_usuario_con_rol_especifico_tiene_solo_sus_permisos(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        $rolVendedor = Rol::where('nombre', 'Vendedor')->first();
        
        // Asignar solo permisos de ver y crear clientes
        $permisoVerClientes = Permiso::where('slug', 'ver-clientes')->first();
        $permisoCrearClientes = Permiso::where('slug', 'crear-clientes')->first();
        
        $rolVendedor->permisos()->attach([$permisoVerClientes->id, $permisoCrearClientes->id]);
        
        $usuario = $this->createUsuario([], ['Vendedor']);

        // Act & Assert
        $this->assertTrue($usuario->hasPermission('ver-clientes'));
        $this->assertTrue($usuario->hasPermission('crear-clientes'));
        $this->assertFalse($usuario->hasPermission('ver-productos'));
        $this->assertFalse($usuario->hasPermission('eliminar-clientes'));
    }

    /**
     * Test: Puede asignar rol a usuario
     */
    public function test_puede_asignar_rol_a_usuario(): void
    {
        // Arrange
        $this->seedRoles();
        $usuario = $this->createUsuario();
        $rol = Rol::where('nombre', 'Gerente')->first();

        // Act
        $usuario->roles()->attach($rol->id);

        // Assert
        $this->assertTrue($usuario->hasRole('Gerente'));
        $this->assertCount(1, $usuario->roles);
    }

    /**
     * Test: Puede remover rol de usuario
     */
    public function test_puede_remover_rol_de_usuario(): void
    {
        // Arrange
        $this->seedRoles();
        $usuario = $this->createUsuario([], ['Vendedor']);

        // Verificar que tiene el rol
        $this->assertTrue($usuario->hasRole('Vendedor'));

        // Act
        $rol = Rol::where('nombre', 'Vendedor')->first();
        $usuario->roles()->detach($rol->id);
        $usuario->refresh();

        // Assert
        $this->assertFalse($usuario->hasRole('Vendedor'));
        $this->assertCount(0, $usuario->roles);
    }

    /**
     * Test: Usuario puede tener múltiples roles
     */
    public function test_usuario_puede_tener_multiples_roles(): void
    {
        // Arrange
        $this->seedRoles();
        $usuario = $this->createUsuario([], ['Vendedor', 'Bodeguero']);

        // Assert
        $this->assertTrue($usuario->hasRole('Vendedor'));
        $this->assertTrue($usuario->hasRole('Bodeguero'));
        $this->assertCount(2, $usuario->roles);
    }

    /**
     * Test: Permisos se heredan de todos los roles del usuario
     */
    public function test_permisos_se_heredan_de_todos_los_roles(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        $rolVendedor = Rol::where('nombre', 'Vendedor')->first();
        $rolBodeguero = Rol::where('nombre', 'Bodeguero')->first();
        
        // Vendedor: permisos de clientes
        $permisoVerClientes = Permiso::where('slug', 'ver-clientes')->first();
        $rolVendedor->permisos()->attach($permisoVerClientes->id);
        
        // Bodeguero: permisos de productos
        $permisoVerProductos = Permiso::where('slug', 'ver-productos')->first();
        $rolBodeguero->permisos()->attach($permisoVerProductos->id);
        
        $usuario = $this->createUsuario([], ['Vendedor', 'Bodeguero']);

        // Assert: Tiene permisos de ambos roles
        $this->assertTrue($usuario->hasPermission('ver-clientes'));
        $this->assertTrue($usuario->hasPermission('ver-productos'));
    }

    /**
     * Test: Rol inactivo no otorga permisos
     */
    public function test_rol_inactivo_no_otorga_permisos(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        $rol = Rol::where('nombre', 'Vendedor')->first();
        $permiso = Permiso::where('slug', 'ver-clientes')->first();
        $rol->permisos()->attach($permiso->id);
        
        // Desactivar el rol
        $rol->update(['activo' => false]);
        
        $usuario = $this->createUsuario([], ['Vendedor']);

        // Assert: El permiso no está activo porque el rol está inactivo
        $this->assertFalse($usuario->hasPermission('ver-clientes'));
    }

    /**
     * Test: Puede listar todos los permisos disponibles
     */
    public function test_puede_listar_todos_los_permisos(): void
    {
        // Arrange
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();

        // Act
        $response = $this->authenticatedJson('GET', '/api/permisos', [], $usuario);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nombre',
                        'slug',
                        'modulo',
                    ],
                ],
            ]);
    }

    /**
     * Test: Puede listar todos los roles
     */
    public function test_puede_listar_todos_los_roles(): void
    {
        // Arrange
        $this->seedRoles();
        $usuario = $this->createAdminUsuario();

        // Act
        $response = $this->authenticatedJson('GET', '/api/roles', [], $usuario);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'nombre',
                        'descripcion',
                    ],
                ],
            ]);
    }

    /**
     * Test: Puede asignar permisos a un rol
     */
    public function test_puede_asignar_permisos_a_rol(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        
        $rol = Rol::where('nombre', 'Vendedor')->first();
        $permiso = Permiso::where('slug', 'ver-productos')->first();

        // Act
        $response = $this->authenticatedJson('POST', "/api/roles/{$rol->id}/permisos", [
            'permiso_id' => $permiso->id,
        ], $usuario);

        // Assert
        $response->assertStatus(200);
        $this->assertTrue($rol->fresh()->hasPermission('ver-productos'));
    }

    /**
     * Test: Puede remover permisos de un rol
     */
    public function test_puede_remover_permisos_de_rol(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        
        $rol = Rol::where('nombre', 'Vendedor')->first();
        $permiso = Permiso::where('slug', 'ver-productos')->first();
        
        // Asignar permiso primero
        $rol->permisos()->attach($permiso->id);
        $this->assertTrue($rol->hasPermission('ver-productos'));

        // Act
        $response = $this->authenticatedJson('DELETE', "/api/roles/{$rol->id}/permisos/{$permiso->id}", [], $usuario);

        // Assert
        $response->assertStatus(200);
        $this->assertFalse($rol->fresh()->hasPermission('ver-productos'));
    }

    /**
     * Test: Permisos están agrupados por módulo
     */
    public function test_permisos_agrupados_por_modulo(): void
    {
        // Arrange
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();

        // Act
        $response = $this->authenticatedJson('GET', '/api/permisos/grouped', [], $usuario);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'Productos' => [],
                    'Clientes' => [],
                ],
            ]);
    }

    /**
     * Test: Middleware verifica permisos correctamente
     */
    public function test_middleware_verifica_permisos_correctamente(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        $rol = Rol::where('nombre', 'Vendedor')->first();
        $permiso = Permiso::where('slug', 'ver-clientes')->first();
        $rol->permisos()->attach($permiso->id);
        
        $usuario = $this->createUsuario([], ['Vendedor']);

        // Act: Intentar acceder a endpoint que requiere permiso 'ver-productos' (no lo tiene)
        $response = $this->authenticatedJson('GET', '/api/productos', [], $usuario);

        // Assert: Debería ser denegado (403 Forbidden)
        $response->assertStatus(403);
    }

    /**
     * Test: Usuario obtiene lista de sus permisos
     */
    public function test_usuario_obtiene_lista_de_sus_permisos(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        $rol = Rol::where('nombre', 'Vendedor')->first();
        $permisoVer = Permiso::where('slug', 'ver-clientes')->first();
        $permisoCrear = Permiso::where('slug', 'crear-clientes')->first();
        $rol->permisos()->attach([$permisoVer->id, $permisoCrear->id]);
        
        $usuario = $this->createUsuario([], ['Vendedor']);

        // Act
        $response = $this->authenticatedJson('GET', '/api/user/permissions', [], $usuario);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'permissions' => [],
            ]);
        
        $permissions = $response->json('permissions');
        $this->assertContains('ver-clientes', $permissions);
        $this->assertContains('crear-clientes', $permissions);
        $this->assertNotContains('ver-productos', $permissions);
    }

    /**
     * Test: Crear rol requiere nombre único
     */
    public function test_crear_rol_requiere_nombre_unico(): void
    {
        // Arrange
        $this->seedRoles();
        $usuario = $this->createAdminUsuario();

        // Act: Intentar crear rol con nombre existente
        $response = $this->authenticatedJson('POST', '/api/roles', [
            'nombre' => 'Administrador', // Ya existe
            'descripcion' => 'Descripción de prueba',
        ], $usuario);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre']);
    }

    /**
     * Test: Solo usuarios con permiso pueden gestionar roles
     */
    public function test_solo_usuarios_con_permiso_pueden_gestionar_roles(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        // Usuario sin permisos de gestión de roles
        $usuario = $this->createUsuario([], ['Vendedor']);

        // Act: Intentar crear rol
        $response = $this->authenticatedJson('POST', '/api/roles', [
            'nombre' => 'Nuevo Rol',
            'descripcion' => 'Test',
        ], $usuario);

        // Assert
        $response->assertStatus(403);
    }
}
