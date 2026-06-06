<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Usuario;
use Tests\TestCase;

class TenantApiKeyTest extends TestCase
{
    /**
     * Test: Un usuario autenticado puede generar una nueva llave de API.
     */
    public function test_usuario_autenticado_puede_generar_llave_de_api(): void
    {
        $usuario = $this->createUsuario();
        
        $response = $this->authenticatedJson('POST', '/api/v5/tenant/api-keys', [
            'name' => 'Facturador Web Principal',
            'environment' => 'live',
        ], $usuario);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'name',
                'prefix',
                'token',
                'environment',
                'created_at',
                'message',
            ]);

        $this->assertEquals('Facturador Web Principal', $response->json('name'));
        $this->assertEquals('sl_live_', $response->json('prefix'));
        $this->assertEquals('live', $response->json('environment'));
        $this->assertStringStartsWith('sl_live_', $response->json('token'));

        // Verificar persistencia en base de datos
        $this->assertDatabaseHas('api_keys', [
            'empresa_id' => $usuario->empresa_id,
            'name' => 'Facturador Web Principal',
            'environment' => 'live',
            'activo' => true,
        ]);
    }

    /**
     * Test: Un usuario autenticado puede listar sus llaves de API activas.
     */
    public function test_usuario_autenticado_puede_listar_sus_llaves_de_api(): void
    {
        $usuario = $this->createUsuario();
        
        // Crear algunas llaves previas
        ApiKey::create([
            'empresa_id' => $usuario->empresa_id,
            'name' => 'Key 1',
            'prefix' => 'sl_live_',
            'token_hash' => hash('sha256', 'sl_live_token1'),
            'environment' => 'live',
            'activo' => true,
        ]);

        ApiKey::create([
            'empresa_id' => $usuario->empresa_id,
            'name' => 'Key 2 (Inactiva)',
            'prefix' => 'sl_sandbox_',
            'token_hash' => hash('sha256', 'sl_sandbox_token2'),
            'environment' => 'sandbox',
            'activo' => false,
        ]);

        $response = $this->authenticatedJson('GET', '/api/v5/tenant/api-keys', [], $usuario);

        $response->assertStatus(200)
            ->assertJsonCount(1); // Solo la llave activa debe listarse

        $response->assertJsonFragment([
            'name' => 'Key 1',
            'prefix' => 'sl_live_',
            'environment' => 'live',
        ]);
    }

    /**
     * Test: Un usuario autenticado puede revocar una llave de API.
     */
    public function test_usuario_autenticado_puede_revocar_llave_de_api(): void
    {
        $usuario = $this->createUsuario();
        
        $key = ApiKey::create([
            'empresa_id' => $usuario->empresa_id,
            'name' => 'Key to Revoke',
            'prefix' => 'sl_live_',
            'token_hash' => hash('sha256', 'sl_live_token_revoke'),
            'environment' => 'live',
            'activo' => true,
        ]);

        $response = $this->authenticatedJson('POST', "/api/v5/tenant/api-keys/{$key->id}/revoke", [], $usuario);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Llave de API revocada con éxito.',
            ]);

        // Verificar cambio de estado en la base de datos
        $this->assertDatabaseHas('api_keys', [
            'id' => $key->id,
            'activo' => false,
        ]);
    }

    /**
     * Test: Un usuario no autenticado no puede acceder a las llaves de API.
     */
    public function test_usuario_no_autenticado_no_puede_acceder_a_llaves_de_api(): void
    {
        $response = $this->getJson('/api/v5/tenant/api-keys');
        $response->assertStatus(401);

        $responsePost = $this->postJson('/api/v5/tenant/api-keys', [
            'name' => 'Anon',
            'environment' => 'sandbox',
        ]);
        $responsePost->assertStatus(401);
    }
}
