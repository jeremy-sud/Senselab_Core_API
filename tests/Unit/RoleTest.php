<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\Usuario;

class RoleTest extends TestCase
{
    /**
     * Test: Rol puede tener múltiples permisos
     */
    public function test_rol_puede_tener_multiples_permisos(): void
    {
        // Arrange
        $this->seedPermisos();
        $rol = Rol::create([
            'nombre' => 'Test Role',
            'descripcion' => 'Rol de prueba',
        ]);

        $permisos = Permiso::limit(3)->get();

        // Act
        $rol->permisos()->attach($permisos->pluck('id'));

        // Assert
        $this->assertCount(3, $rol->permisos);
    }

    /**
     * Test: Método hasPermission verifica permiso por slug
     */
    public function test_has_permission_verifica_permiso_por_slug(): void
    {
        // Arrange
        $this->seedPermisos();
        $rol = Rol::create([
            'nombre' => 'Test Role',
            'descripcion' => 'Rol de prueba',
        ]);

        $permiso = Permiso::where('slug', 'ver-productos')->first();
        $rol->permisos()->attach($permiso->id);

        // Act & Assert
        $this->assertTrue($rol->hasPermission('ver-productos'));
        $this->assertFalse($rol->hasPermission('crear-productos'));
    }

    /**
     * Test: Rol tiene relación con usuarios
     */
    public function test_rol_tiene_relacion_con_usuarios(): void
    {
        // Arrange
        $this->seedRoles();
        $rol = Rol::where('nombre', 'Administrador')->first();
        
        $usuario1 = $this->createUsuario([], ['Administrador']);
        $usuario2 = $this->createUsuario(['email' => 'user2@test.com'], ['Administrador']);

        // Act
        $usuarios = $rol->usuarios;

        // Assert
        $this->assertCount(2, $usuarios);
        $this->assertTrue($usuarios->contains($usuario1));
        $this->assertTrue($usuarios->contains($usuario2));
    }

    /**
     * Test: Scope activos filtra solo roles activos
     */
    public function test_scope_activos_filtra_roles_activos(): void
    {
        // Arrange
        Rol::create(['nombre' => 'Rol Activo', 'activo' => true]);
        Rol::create(['nombre' => 'Rol Inactivo', 'activo' => false]);

        // Act
        $rolesActivos = Rol::activos()->get();

        // Assert
        $this->assertCount(1, $rolesActivos);
        $this->assertEquals('Rol Activo', $rolesActivos->first()->nombre);
    }

    /**
     * Test: Nombre de rol se normaliza al guardar
     */
    public function test_nombre_rol_se_normaliza_al_guardar(): void
    {
        // Arrange & Act
        $rol = Rol::create([
            'nombre' => 'test role',
            'descripcion' => 'Prueba',
        ]);

        // Assert
        $this->assertEquals('Test role', $rol->nombre);
    }

    /**
     * Test: Puede eliminar permiso de rol
     */
    public function test_puede_eliminar_permiso_de_rol(): void
    {
        // Arrange
        $this->seedPermisos();
        $rol = Rol::create(['nombre' => 'Test Role']);
        
        $permiso = Permiso::where('slug', 'ver-productos')->first();
        $rol->permisos()->attach($permiso->id);
        
        $this->assertTrue($rol->hasPermission('ver-productos'));

        // Act
        $rol->permisos()->detach($permiso->id);
        $rol->refresh();

        // Assert
        $this->assertFalse($rol->hasPermission('ver-productos'));
        $this->assertCount(0, $rol->permisos);
    }

    /**
     * Test: Rol tiene timestamps personalizados
     */
    public function test_rol_tiene_timestamps_personalizados(): void
    {
        // Arrange & Act
        $rol = Rol::create([
            'nombre' => 'Test Role',
            'descripcion' => 'Prueba',
        ]);

        // Assert
        $this->assertNotNull($rol->creado_en);
        $this->assertNotNull($rol->actualizado_en);
        $this->assertInstanceOf(\DateTime::class, $rol->creado_en);
    }

    /**
     * Test: Eliminación de rol no elimina usuarios asociados
     */
    public function test_eliminacion_rol_no_elimina_usuarios(): void
    {
        // Arrange
        $this->seedRoles();
        $rol = Rol::where('nombre', 'Administrador')->first();
        $usuario = $this->createUsuario([], ['Administrador']);

        // Act
        $rol->delete();

        // Assert
        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id]);
    }

    /**
     * Test: Puede sincronizar permisos de un rol
     */
    public function test_puede_sincronizar_permisos_de_rol(): void
    {
        // Arrange
        $this->seedPermisos();
        $rol = Rol::create(['nombre' => 'Test Role']);
        
        $permiso1 = Permiso::where('slug', 'ver-productos')->first();
        $permiso2 = Permiso::where('slug', 'crear-productos')->first();
        $permiso3 = Permiso::where('slug', 'editar-productos')->first();
        
        // Asignar permisos iniciales
        $rol->permisos()->attach([$permiso1->id, $permiso2->id]);
        $this->assertCount(2, $rol->permisos);

        // Act: Sincronizar con nuevos permisos
        $rol->permisos()->sync([$permiso2->id, $permiso3->id]);
        $rol->refresh();

        // Assert
        $this->assertCount(2, $rol->permisos);
        $this->assertFalse($rol->hasPermission('ver-productos')); // Removido
        $this->assertTrue($rol->hasPermission('crear-productos')); // Mantenido
        $this->assertTrue($rol->hasPermission('editar-productos')); // Agregado
    }

    /**
     * Test: Validación de campos requeridos
     */
    public function test_validacion_campos_requeridos(): void
    {
        // Expect exception when creating without required fields
        $this->expectException(\Illuminate\Database\QueryException::class);

        Rol::create([
            'descripcion' => 'Solo descripción',
            // Falta 'nombre' que es requerido
        ]);
    }
}
