<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\CuentaBancaria;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CuentaBancariaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear cuenta bancaria con IBAN Costa Rica válido
     */
    public function test_puede_crear_cuenta_con_iban_cr_valido(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $data = [
            'empresa_id' => $empresa->id,
            'banco' => 'Banco Nacional de Costa Rica',
            'numero_cuenta' => '100-01-123-456789-0',
            'iban' => 'CR12345678901234567890', // IBAN válido CR
            'tipo_cuenta' => 'corriente',
            'moneda' => 'CRC',
            'titular' => 'Empresa Demo SA',
            'activa' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/cuentas-bancarias', $data, $usuario);

        $response->assertStatus(201)
            ->assertJsonPath('data.iban', 'CR12345678901234567890')
            ->assertJsonPath('data.moneda', 'CRC');

        $this->assertDatabaseHas('cuentas_bancarias', [
            'iban' => 'CR12345678901234567890',
        ]);
    }

    /**
     * Test: Validar formato IBAN Costa Rica
     */
    public function test_valida_formato_iban_costa_rica(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        // IBAN sin prefijo CR
        $data = [
            'empresa_id' => $empresa->id,
            'banco' => 'Banco Nacional',
            'numero_cuenta' => '100-01-123-456789-0',
            'iban' => '12345678901234567890', // Sin CR
            'tipo_cuenta' => 'corriente',
            'moneda' => 'CRC',
        ];

        $response = $this->authenticatedJson('POST', '/api/cuentas-bancarias', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['iban']);
    }

    /**
     * Test: Validar longitud IBAN (22 caracteres)
     */
    public function test_valida_longitud_iban_22_caracteres(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $data = [
            'empresa_id' => $empresa->id,
            'banco' => 'Banco Nacional',
            'numero_cuenta' => '100-01-123-456789-0',
            'iban' => 'CR123456789', // Muy corto
            'tipo_cuenta' => 'corriente',
            'moneda' => 'CRC',
        ];

        $response = $this->authenticatedJson('POST', '/api/cuentas-bancarias', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['iban']);
    }

    /**
     * Test: Validar IBAN único
     */
    public function test_valida_iban_unico(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $ibanExistente = 'CR11111111111111111111';

        CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
            'iban' => $ibanExistente,
        ]);

        $data = [
            'empresa_id' => $empresa->id,
            'banco' => 'Banco Nacional',
            'numero_cuenta' => '999-99-999-999999-9',
            'iban' => $ibanExistente, // Duplicado
            'tipo_cuenta' => 'corriente',
            'moneda' => 'CRC',
        ];

        $response = $this->authenticatedJson('POST', '/api/cuentas-bancarias', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['iban']);
    }

    /**
     * Test: Filtrar por moneda
     */
    public function test_puede_filtrar_por_moneda(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
            'moneda' => 'CRC',
        ]);
        CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
            'moneda' => 'USD',
        ]);

        $response = $this->authenticatedJson('GET', '/api/cuentas-bancarias?moneda=CRC', [], $usuario);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('CRC', $response->json('data.0.moneda'));
    }

    /**
     * Test: Número de cuenta enmascarado en resource
     */
    public function test_numero_cuenta_enmascarado_en_response(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $cuenta = CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
            'numero_cuenta' => '100-01-123-456789-0',
        ]);

        $response = $this->authenticatedJson('GET', "/api/cuentas-bancarias/{$cuenta->id}", [], $usuario);

        $response->assertStatus(200);
        
        $numeroCuenta = $response->json('data.numero_cuenta');
        $this->assertStringContainsString('*', $numeroCuenta);
        $this->assertStringNotContainsString('456789', $numeroCuenta);
    }

    /**
     * Test: Validar tipos de cuenta permitidos
     */
    public function test_valida_tipos_cuenta_permitidos(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $data = [
            'empresa_id' => $empresa->id,
            'banco' => 'Banco Nacional',
            'numero_cuenta' => '100-01-123-456789-0',
            'tipo_cuenta' => 'invalido', // Tipo no permitido
            'moneda' => 'CRC',
        ];

        $response = $this->authenticatedJson('POST', '/api/cuentas-bancarias', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_cuenta']);
    }

    /**
     * Test: Validar monedas permitidas
     */
    public function test_valida_monedas_permitidas(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $data = [
            'empresa_id' => $empresa->id,
            'banco' => 'Banco Nacional',
            'numero_cuenta' => '100-01-123-456789-0',
            'tipo_cuenta' => 'corriente',
            'moneda' => 'JPY', // Moneda no permitida
        ];

        $response = $this->authenticatedJson('POST', '/api/cuentas-bancarias', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['moneda']);
    }
}
