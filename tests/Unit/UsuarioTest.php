<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\Empresa;
use App\Models\Cargo;

class UsuarioTest extends TestCase
{
    /**
     * Test: Usuario pertenece a una empresa
     */
    public function test_usuario_pertenece_a_empresa(): void
    {
        // Arrange
        $empresa = $this->createEmpresa(['nombre' => 'Mi Empresa']);
        $usuario = $this->createUsuario(['empresa_id' => $empresa->id]);

        // Act
        $empresaDelUsuario = $usuario->empresa;

        // Assert
        $this->assertInstanceOf(Empresa::class, $empresaDelUsuario);
        $this->assertEquals('Mi Empresa', $empresaDelUsuario->nombre);
    }

    /**
     * Test: Usuario puede tener múltiples roles
     */
    public function test_usuario_puede_tener_multiples_roles(): void
    {
        // Arrange
        $this->seedRoles();
        $usuario = $this->createUsuario([], ['Administrador', 'Gerente']);

        // Act
        $roles = $usuario->roles;

        // Assert
        $this->assertCount(2, $roles);
        $this->assertTrue($roles->contains('nombre', 'Administrador'));
        $this->assertTrue($roles->contains('nombre', 'Gerente'));
    }

    /**
     * Test: Método hasRole verifica si usuario tiene un rol
     */
    public function test_has_role_verifica_si_usuario_tiene_rol(): void
    {
        // Arrange
        $this->seedRoles();
        $usuario = $this->createUsuario([], ['Vendedor']);

        // Act & Assert
        $this->assertTrue($usuario->hasRole('Vendedor'));
        $this->assertFalse($usuario->hasRole('Administrador'));
    }

    /**
     * Test: Método hasPermission verifica permisos a través de roles
     */
    public function test_has_permission_verifica_permisos_a_traves_de_roles(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        $rol = Rol::where('nombre', 'Vendedor')->first();
        $permiso = Permiso::where('slug', 'ver-clientes')->first();
        $rol->permisos()->attach($permiso->id);
        
        $usuario = $this->createUsuario([], ['Vendedor']);

        // Act & Assert
        $this->assertTrue($usuario->hasPermission('ver-clientes'));
        $this->assertFalse($usuario->hasPermission('eliminar-clientes'));
    }

    /**
     * Test: Usuario sin roles no tiene permisos
     */
    public function test_usuario_sin_roles_no_tiene_permisos(): void
    {
        // Arrange
        $usuario = $this->createUsuario(); // Sin roles

        // Act & Assert
        $this->assertFalse($usuario->hasPermission('ver-productos'));
        $this->assertCount(0, $usuario->roles);
    }

    /**
     * Test: Método getAllPermissions retorna todos los permisos del usuario
     */
    public function test_get_all_permissions_retorna_todos_los_permisos(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        $rolVendedor = Rol::where('nombre', 'Vendedor')->first();
        $rolBodeguero = Rol::where('nombre', 'Bodeguero')->first();
        
        $permisoClientes = Permiso::where('slug', 'ver-clientes')->first();
        $permisoProductos = Permiso::where('slug', 'ver-productos')->first();
        
        $rolVendedor->permisos()->attach($permisoClientes->id);
        $rolBodeguero->permisos()->attach($permisoProductos->id);
        
        $usuario = $this->createUsuario([], ['Vendedor', 'Bodeguero']);

        // Act
        $permisos = $usuario->getAllPermissions();

        // Assert
        $this->assertIsArray($permisos);
        $this->assertContains('ver-clientes', $permisos);
        $this->assertContains('ver-productos', $permisos);
    }

    /**
     * Test: Password hash se usa para autenticación
     */
    public function test_password_hash_se_usa_para_autenticacion(): void
    {
        // Arrange
        $usuario = $this->createUsuario([
            'password_hash' => bcrypt('test123'),
        ]);

        // Act
        $authPassword = $usuario->getAuthPassword();

        // Assert
        $this->assertNotNull($authPassword);
        $this->assertTrue(\Hash::check('test123', $authPassword));
    }

    /**
     * Test: Usuario puede tener un cargo
     */
    public function test_usuario_puede_tener_cargo(): void
    {
        // Arrange
        $cargo = Cargo::create([
            'nombre' => 'Desarrollador',
            'activo' => true,
            'eliminado' => false,
        ]);

        $usuario = $this->createUsuario(['cargo_id' => $cargo->id]);

        // Act
        $cargoDelUsuario = $usuario->cargo;

        // Assert
        $this->assertInstanceOf(Cargo::class, $cargoDelUsuario);
        $this->assertEquals('Desarrollador', $cargoDelUsuario->nombre);
    }

    /**
     * Test: Timestamps personalizados funcionan correctamente
     */
    public function test_timestamps_personalizados_funcionan(): void
    {
        // Arrange & Act
        $usuario = $this->createUsuario();

        // Assert
        $this->assertNotNull($usuario->creado_en);
        $this->assertNotNull($usuario->actualizado_en);
        $this->assertInstanceOf(\DateTime::class, $usuario->creado_en);
        $this->assertInstanceOf(\DateTime::class, $usuario->actualizado_en);
    }

    /**
     * Test: Usuario activo puede iniciar sesión
     */
    public function test_usuario_activo_puede_iniciar_sesion(): void
    {
        // Arrange
        $usuario = $this->createUsuario(['activo' => true]);

        // Act & Assert
        $this->assertTrue($usuario->activo);
    }

    /**
     * Test: Usuario inactivo no puede iniciar sesión
     */
    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        // Arrange
        $usuario = $this->createUsuario(['activo' => false]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(401)
            ->assertJson(['message' => 'Usuario inactivo']);
    }

    /**
     * Test: Usuario puede crear tokens de autenticación (Sanctum)
     */
    public function test_usuario_puede_crear_tokens_sanctum(): void
    {
        // Arrange
        $usuario = $this->createUsuario();

        // Act
        $token = $usuario->createToken('test-token');

        // Assert
        $this->assertNotNull($token);
        $this->assertNotEmpty($token->plainTextToken);
        $this->assertEquals(1, $usuario->tokens()->count());
    }

    /**
     * Test: Usuario puede tener múltiples tokens
     */
    public function test_usuario_puede_tener_multiples_tokens(): void
    {
        // Arrange
        $usuario = $this->createUsuario();

        // Act
        $usuario->createToken('device-1');
        $usuario->createToken('device-2');
        $usuario->createToken('device-3');

        // Assert
        $this->assertEquals(3, $usuario->tokens()->count());
    }

    /**
     * Test: Permisos de roles inactivos no se cuentan
     */
    public function test_permisos_de_roles_inactivos_no_se_cuentan(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        $rol = Rol::where('nombre', 'Vendedor')->first();
        $permiso = Permiso::where('slug', 'ver-clientes')->first();
        $rol->permisos()->attach($permiso->id);
        
        $usuario = $this->createUsuario([], ['Vendedor']);
        
        // Verificar que tiene el permiso
        $this->assertTrue($usuario->hasPermission('ver-clientes'));

        // Act: Desactivar el rol
        $rol->update(['activo' => false]);
        $usuario->refresh();

        // Assert: Ya no tiene el permiso
        $this->assertFalse($usuario->hasPermission('ver-clientes'));
    }

    /**
     * Test: Email debe ser único
     */
    public function test_email_debe_ser_unico(): void
    {
        // Arrange
        $this->createUsuario(['email' => 'duplicado@test.com']);

        // Expect exception when creating with duplicate email
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Act
        $this->createUsuario(['email' => 'duplicado@test.com']);
    }

    /**
     * Test: Campos fillable permiten asignación masiva
     */
    public function test_campos_fillable_permiten_asignacion_masiva(): void
    {
        // Arrange
        $empresa = $this->createEmpresa();
        
        // Act
        $usuario = Usuario::create([
            'nombre' => 'Juan',
            'apellidos' => 'Pérez',
            'email' => 'juan.perez@test.com',
            'password_hash' => bcrypt('password'),
            'empresa_id' => $empresa->id,
            'telefono' => '+506 8888-9999',
            'direccion' => 'San José',
            'activo' => true,
            'eliminado' => false,
        ]);

        // Assert
        $this->assertEquals('Juan', $usuario->nombre);
        $this->assertEquals('Pérez', $usuario->apellidos);
        $this->assertEquals('+506 8888-9999', $usuario->telefono);
    }
}
