<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    /**
     * Test: Login exitoso con credenciales válidas
     */
    public function test_usuario_puede_hacer_login_con_credenciales_validas(): void
    {
        // Arrange: Crear empresa y usuario
        $empresa = $this->createEmpresa();
        $usuario = Usuario::create([
            'nombre' => 'Admin',
            'apellidos' => 'Sistema',
            'email' => 'admin@test.com',
            'password_hash' => bcrypt('password123'),
            'empresa_id' => $empresa->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Act: Intentar login
        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        // Assert: Verificar respuesta exitosa
        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'nombre',
                    'email',
                    'empresa_id',
                ],
                'token',
                'permissions',
            ]);

        $this->assertNotEmpty($response->json('token'));
    }

    /**
     * Test: Login falla con credenciales inválidas
     */
    public function test_login_falla_con_credenciales_invalidas(): void
    {
        // Arrange
        $empresa = $this->createEmpresa();
        Usuario::create([
            'nombre' => 'Admin',
            'email' => 'admin@test.com',
            'password_hash' => bcrypt('password123'),
            'empresa_id' => $empresa->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Act: Intentar login con password incorrecta
        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong_password',
        ]);

        // Assert: Debe fallar
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: Login falla con usuario inexistente
     */
    public function test_login_falla_con_usuario_inexistente(): void
    {
        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'noexiste@test.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: Login falla con usuario inactivo
     */
    public function test_login_falla_con_usuario_inactivo(): void
    {
        // Arrange
        $empresa = $this->createEmpresa();
        Usuario::create([
            'nombre' => 'Usuario',
            'email' => 'inactivo@test.com',
            'password_hash' => bcrypt('password123'),
            'empresa_id' => $empresa->id,
            'activo' => false, // Usuario inactivo
            'eliminado' => false,
        ]);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => 'inactivo@test.com',
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test: Login requiere validación de campos
     */
    public function test_login_requiere_email_y_password(): void
    {
        // Act: Login sin email
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        // Act: Login sin password
        $response = $this->postJson('/api/login', [
            'email' => 'test@test.com',
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test: Logout destruye el token
     */
    public function test_usuario_puede_hacer_logout(): void
    {
        // Arrange
        $usuario = $this->createUsuario();
        $token = $usuario->createToken('test-token')->plainTextToken;

        // Act: Hacer logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/logout');

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Sesión cerrada exitosamente',
            ]);

        // Verificar que el token fue eliminado de la base de datos
        $this->assertDatabaseMissing('personal_access_tokens', [
            'tokenable_id' => $usuario->id,
            'tokenable_type' => get_class($usuario),
        ]);
    }

    /**
     * Test: Usuario autenticado puede obtener su información
     */
    public function test_usuario_autenticado_puede_obtener_su_informacion(): void
    {
        // Arrange
        $usuario = $this->createUsuario([
            'nombre' => 'Juan',
            'apellidos' => 'Pérez',
            'email' => 'juan@test.com',
        ]);

        // Act
        $response = $this->authenticatedJson('GET', '/api/user', [], $usuario);

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'nombre' => 'Juan',
                'apellidos' => 'Pérez',
                'email' => 'juan@test.com',
            ]);
    }

    /**
     * Test: Login retorna permisos del usuario
     */
    public function test_login_retorna_permisos_del_usuario(): void
    {
        // Arrange
        $this->seedRoles();
        $this->seedPermisos();
        
        $rol = \App\Models\Rol::where('nombre', 'Administrador')->first();
        $this->assignAllPermissionsToRole($rol);

        $usuario = $this->createUsuario([], ['Administrador']);

        // Act
        $response = $this->postJson('/api/login', [
            'email' => $usuario->email,
            'password' => 'password123',
        ]);

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'permissions' => [],
            ]);

        $permissions = $response->json('permissions');
        $this->assertIsArray($permissions);
        $this->assertNotEmpty($permissions);
    }

    /**
     * Test: Token expira después de un tiempo configurado
     */
    public function test_token_tiene_tiempo_de_expiracion(): void
    {
        // Arrange
        $usuario = $this->createUsuario();
        $token = $usuario->createToken('test-token', ['*'], now()->addMinutes(1))->plainTextToken;

        // Act: Usar token antes de expirar
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->getJson('/api/user');

        // Assert: Token válido
        $response->assertStatus(200);
    }

    /**
     * Test: Usuario puede tener múltiples tokens activos
     */
    public function test_usuario_puede_tener_multiples_tokens_activos(): void
    {
        // Arrange
        $usuario = $this->createUsuario();
        $token1 = $usuario->createToken('device-1')->plainTextToken;
        $token2 = $usuario->createToken('device-2')->plainTextToken;

        // Act & Assert: Ambos tokens deben funcionar
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
            'Accept' => 'application/json',
        ])->getJson('/api/user');

        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token2,
            'Accept' => 'application/json',
        ])->getJson('/api/user');

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        // Verificar que hay 2 tokens
        $this->assertEquals(2, $usuario->tokens()->count());
    }

    /**
     * Test: Logout solo elimina el token actual
     */
    public function test_logout_solo_elimina_token_actual(): void
    {
        // Arrange
        $usuario = $this->createUsuario();
        $token1 = $usuario->createToken('device-1')->plainTextToken;
        $token2 = $usuario->createToken('device-2')->plainTextToken;

        // Act: Logout con token1
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token1,
            'Accept' => 'application/json',
        ])->postJson('/api/logout');

        // Assert: Token1 eliminado, Token2 todavía existe
        $response->assertStatus(200);

        // Verificar que solo queda 1 token (token2)
        $this->assertEquals(1, $usuario->fresh()->tokens()->count());
        
        // Verificar que token2 todavía existe en BD
        $this->assertEquals(1, $usuario->fresh()->tokens()->where('name', 'device-2')->count());
    }
}
