<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmpresaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
    }

    /** @test */
    public function test_puede_listar_empresas()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $response = $this->authenticatedJson('GET', '/api/empresas', [], $usuario);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'nombre'
                        ]
                    ],
                    'links',
                    'meta'
                ]);
    }

    /** @test */
    public function test_puede_crear_empresa()
    {
        $usuario = $this->createAdminUsuario();
        
        // Obtener un régimen tributario válido
        $regimenTributario = \App\Models\RegimenTributario::first();

        $empresaData = [
            'nombre' => 'Nueva Empresa Test',
            'nombre_comercial' => 'Nueva Test Corp',
            'razon_social' => 'Nueva Empresa Test S.A.',
            'num_identificacion_dgt' => '3-101-654321',
            'tipo_identificacion' => '02',
            'direccion' => 'Heredia, Costa Rica',
            'provincia' => '4',
            'canton' => '01',
            'distrito' => '01',
            'telefono' => '+506 2555-6666',
            'email' => 'nueva@empresa.com',
            'moneda_defecto' => 'CRC',
            'regimen_tributario_id' => $regimenTributario->id,
        ];

        $response = $this->authenticatedJson('POST', '/api/empresas', $empresaData, $usuario);

        $response->assertStatus(201)
                ->assertJsonFragment([
                    'nombre' => 'Nueva Empresa Test',
                    'email' => 'nueva@empresa.com'
                ]);

        $this->assertDatabaseHas('empresas', [
            'num_identificacion_dgt' => '3-101-654321'
        ]);
    }

    /** @test */
    public function test_puede_actualizar_empresa()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $updateData = [
            'nombre' => 'Empresa Actualizada',
            'telefono' => '+506 2777-8888'
        ];

        $response = $this->authenticatedJson('PUT', "/api/empresas/{$empresa->id}", $updateData, $usuario);

        $response->assertStatus(200)
                ->assertJsonFragment([
                    'nombre' => 'Empresa Actualizada'
                ]);

        $this->assertDatabaseHas('empresas', [
            'id' => $empresa->id,
            'nombre' => 'Empresa Actualizada',
            'telefono' => '+506 2777-8888'
        ]);
    }

    /** @test */
    public function test_puede_ver_empresa_especifica()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $response = $this->authenticatedJson('GET', "/api/empresas/{$empresa->id}", [], $usuario);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'nombre'
                    ]
                ]);
    }

    /** @test */
    public function test_valida_cedula_juridica_unica()
    {
        $usuario = $this->createAdminUsuario();
        
        // Intentar crear empresa con misma cédula jurídica
        $empresaData = [
            'nombre' => 'Empresa Duplicada',
            'nombre_comercial' => 'Test Dup',
            'razon_social' => 'Empresa Duplicada S.A.',
            'num_identificacion_dgt' => $usuario->empresa->num_identificacion_dgt, // Duplicada
            'tipo_identificacion' => '02',
            'direccion' => 'San José',
            'provincia' => '1',
            'canton' => '01',
            'distrito' => '01',
            'telefono' => '+506 2222-3333',
            'email' => 'dup@test.com',
            'moneda_defecto' => 'CRC',
            'regimen_tributario_id' => 1,
        ];

        $response = $this->authenticatedJson('POST', '/api/empresas', $empresaData, $usuario);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['num_identificacion_dgt']);
    }

    /** @test */
    public function test_valida_email_unico()
    {
        $usuario = $this->createAdminUsuario();
        
        // Obtener un régimen tributario válido
        $regimenTributario = \App\Models\RegimenTributario::first();

        $empresaData = [
            'nombre' => 'Empresa Email Dup',
            'nombre_comercial' => 'Email Dup',
            'razon_social' => 'Empresa Email S.A.',
            'num_identificacion_dgt' => '3-101-999888',
            'tipo_identificacion' => '02',
            'direccion' => 'San José',
            'provincia' => '1',
            'canton' => '01',
            'distrito' => '01',
            'telefono' => '+506 2222-3333',
            'email' => $usuario->empresa->email, // Email duplicado
            'moneda_defecto' => 'CRC',
            'regimen_tributario_id' => $regimenTributario->id,
        ];

        $response = $this->authenticatedJson('POST', '/api/empresas', $empresaData, $usuario);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function test_requiere_autenticacion()
    {
        $response = $this->json('GET', '/api/empresas');

        $response->assertStatus(401);
    }

    /** @test */
    public function test_valida_campos_requeridos()
    {
        $usuario = $this->createAdminUsuario();

        $empresaData = []; // Sin datos

        $response = $this->authenticatedJson('POST', '/api/empresas', $empresaData, $usuario);

        $response->assertStatus(422)
                ->assertJsonValidationErrors([
                    'nombre',
                    'num_identificacion_dgt',
                    'regimen_tributario_id'
                ]);
    }
}
