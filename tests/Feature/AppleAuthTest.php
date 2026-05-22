<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Rol;
use App\Models\Cargo;
use Mockery;

class AppleAuthTest extends TestCase
{
    /**
     * Test: Redirección a Apple funciona
     */
    public function test_redireccion_a_apple_funciona(): void
    {
        $response = $this->getJson('/api/auth/apple/redirect?redirect_origin=https://myfrontend.com');

        $response->assertStatus(302);
        $this->assertStringContainsString('appleid.apple.com', $response->headers->get('Location'));
        $this->assertStringContainsString('state=', $response->headers->get('Location'));
    }

    /**
     * Test: Callback de Apple crea nuevo usuario e inicia sesión
     */
    public function test_callback_de_apple_crea_nuevo_usuario_e_inicia_sesion(): void
    {
        // 1. Sembrar roles, cargos y datos iniciales para el test
        Rol::create(['nombre' => 'Administrador', 'activo' => true, 'eliminado' => false]);
        Cargo::create(['nombre' => 'Gerente', 'activo' => true, 'eliminado' => false]);
        Empresa::create([
            'nombre' => 'Test Company',
            'nombre_comercial' => 'Test',
            'razon_social' => 'Test',
            'num_identificacion_dgt' => '3-101-999999',
            'tipo_identificacion' => 'Físico',
            'activo' => true,
        ]);

        // 2. Mockear JWT payload (id_token) de Apple
        $payload = [
            'email' => 'newappleuser@apple.com',
            'sub' => 'apple-sub-123456',
        ];
        
        $header = base64_encode(json_encode(['alg' => 'RS256']));
        $body = base64_encode(json_encode($payload));
        $fakeIdToken = $header . '.' . $body . '.signature';

        // 3. Generar estado encriptado
        $state = encrypt(['redirect_origin' => 'https://myfrontend.com']);

        // 4. Llamar al callback (POST)
        $response = $this->post('/api/auth/apple/callback', [
            'state' => $state,
            'id_token' => $fakeIdToken,
            'user' => json_encode([
                'name' => [
                    'firstName' => 'Apple',
                    'lastName' => 'User',
                ]
            ]),
        ]);

        // 5. Verificar redirección de regreso al frontend
        $response->assertStatus(302);
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('https://myfrontend.com', $location);
        $this->assertStringContainsString('token=', $location);
        $this->assertStringContainsString('user=', $location);

        // 6. Verificar que el usuario fue creado en la base de datos
        $this->assertDatabaseHas('usuarios', [
            'email' => 'newappleuser@apple.com',
            'nombre' => 'Apple',
            'apellidos' => 'User',
            'activo' => true,
        ]);
    }
}
