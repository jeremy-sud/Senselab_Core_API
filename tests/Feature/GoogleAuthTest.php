<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Cargo;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

class GoogleAuthTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test: Redirección a Google funciona
     */
    public function test_redireccion_a_google_funciona(): void
    {
        $response = $this->getJson('/api/auth/google/redirect?redirect_origin=https://myfrontend.com');

        $response->assertStatus(302);
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
        $this->assertStringContainsString('state=', $response->headers->get('Location'));
    }

    /**
     * Test: Callback de Google crea nuevo usuario e inicia sesión
     */
    public function test_callback_de_google_crea_nuevo_usuario_e_inicia_sesion(): void
    {
        // 1. Sembrar roles, cargos y datos iniciales para el test
        $rol = Rol::create(['nombre' => 'Administrador', 'activo' => true, 'eliminado' => false]);
        $cargo = Cargo::create(['nombre' => 'Gerente', 'activo' => true, 'eliminado' => false]);
        $empresa = Empresa::create([
            'nombre' => 'Test Company',
            'nombre_comercial' => 'Test',
            'razon_social' => 'Test',
            'num_identificacion_dgt' => '3-101-999999',
            'tipo_identificacion' => 'Físico',
            'activo' => true,
        ]);

        // 2. Mockear Socialite User
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getEmail')->andReturn('newuser@google.com');
        $googleUser->shouldReceive('getName')->andReturn('John Doe');

        // 3. Mockear Socialite Driver
        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // 4. Generar estado encriptado
        $state = encrypt(['redirect_origin' => 'https://myfrontend.com']);

        // 5. Llamar al callback
        $response = $this->get('/api/auth/google/callback?state=' . urlencode($state));

        // 6. Verificar redirección de regreso al frontend
        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://myfrontend.com', $location);
        $this->assertStringContainsString('token=', $location);
        $this->assertStringContainsString('user=', $location);

        // 7. Verificar que el usuario fue creado en la base de datos
        $this->assertDatabaseHas('usuarios', [
            'email' => 'newuser@google.com',
            'nombre' => 'John',
            'apellidos' => 'Doe',
            'activo' => true,
        ]);
    }

    /**
     * Test: Callback de Google inicia sesión de usuario existente
     */
    public function test_callback_de_google_inicia_sesion_de_usuario_existente(): void
    {
        // 1. Crear empresa y usuario existente
        $empresa = Empresa::create([
            'nombre' => 'Test Company',
            'nombre_comercial' => 'Test',
            'razon_social' => 'Test',
            'num_identificacion_dgt' => '3-101-999999',
            'tipo_identificacion' => 'Físico',
            'activo' => true,
        ]);
        $usuario = Usuario::create([
            'nombre' => 'Jane',
            'apellidos' => 'Smith',
            'email' => 'existing@google.com',
            'password_hash' => bcrypt('secret'),
            'empresa_id' => $empresa->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        // 2. Mockear Socialite User
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getEmail')->andReturn('existing@google.com');
        $googleUser->shouldReceive('getName')->andReturn('Jane Smith');

        // 3. Mockear Socialite Driver
        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($googleUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        // 4. Generar estado encriptado
        $state = encrypt(['redirect_origin' => 'https://myfrontend.com']);

        // 5. Llamar al callback
        $response = $this->get('/api/auth/google/callback?state=' . urlencode($state));

        // 6. Verificar redirección
        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://myfrontend.com', $location);
        $this->assertStringContainsString('token=', $location);
        
        $decodedUrl = urldecode($location);
        $this->assertStringContainsString('"email":"existing@google.com"', $decodedUrl);
    }
}
