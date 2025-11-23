<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\DeclaracionTributaria;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeclaracionTributariaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Listar declaraciones tributarias autenticado
     */
    public function test_puede_listar_declaraciones_autenticado(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        DeclaracionTributaria::factory()->create([
            'empresa_id' => $empresa->id,
            'tipo_declaracion' => 'D104',
            'periodo_fiscal' => '2025-01',
        ]);

        $response = $this->authenticatedJson('GET', '/api/declaraciones-tributarias', [], $usuario);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'tipo_declaracion', 'periodo_fiscal', 'estado'],
                ],
            ]);
    }

    /**
     * Test: Crear declaración D104 (IVA) con datos válidos
     */
    public function test_puede_crear_declaracion_d104_valida(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $data = [
            'empresa_id' => $empresa->id,
            'tipo_declaracion' => 'D104',
            'periodo_fiscal' => '2025-01',
            'fecha_inicio_periodo' => '2025-01-01',
            'fecha_fin_periodo' => '2025-01-31',
            'monto_base_imponible' => 1500000.00,
            'monto_impuesto' => 195000.00,
            'monto_creditos' => 104000.00,
            'monto_debitos' => 195000.00,
            'monto_a_pagar' => 91000.00,
            'monto_a_favor' => 0.00,
            'estado' => 'borrador',
        ];

        $response = $this->authenticatedJson('POST', '/api/declaraciones-tributarias', $data, $usuario);

        $response->assertStatus(201)
            ->assertJsonPath('data.tipo_declaracion', 'D104')
            ->assertJsonPath('data.periodo_fiscal', '2025-01')
            ->assertJsonPath('data.monto_a_pagar', 91000);

        $this->assertDatabaseHas('declaraciones_tributarias', [
            'empresa_id' => $empresa->id,
            'tipo_declaracion' => 'D104',
            'periodo_fiscal' => '2025-01',
        ]);
    }

    /**
     * Test: Validar periodo fiscal formato YYYY-MM
     */
    public function test_valida_periodo_fiscal_formato_yyyy_mm(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $data = [
            'empresa_id' => $empresa->id,
            'tipo_declaracion' => 'D104',
            'periodo_fiscal' => '2025/01', // Formato inválido
            'fecha_inicio_periodo' => '2025-01-01',
            'fecha_fin_periodo' => '2025-01-31',
        ];

        $response = $this->authenticatedJson('POST', '/api/declaraciones-tributarias', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['periodo_fiscal']);
    }

    /**
     * Test: Validar tipo declaración permitido
     */
    public function test_valida_tipo_declaracion_permitido(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $data = [
            'empresa_id' => $empresa->id,
            'tipo_declaracion' => 'D999', // Tipo no permitido
            'periodo_fiscal' => '2025-01',
            'fecha_inicio_periodo' => '2025-01-01',
            'fecha_fin_periodo' => '2025-01-31',
        ];

        $response = $this->authenticatedJson('POST', '/api/declaraciones-tributarias', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_declaracion']);
    }

    /**
     * Test: Filtrar por tipo de declaración
     */
    public function test_puede_filtrar_por_tipo_declaracion(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        DeclaracionTributaria::factory()->create([
            'empresa_id' => $empresa->id,
            'tipo_declaracion' => 'D104',
        ]);
        DeclaracionTributaria::factory()->create([
            'empresa_id' => $empresa->id,
            'tipo_declaracion' => 'D101',
        ]);

        $response = $this->authenticatedJson('GET', '/api/declaraciones-tributarias?tipo_declaracion=D104', [], $usuario);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('D104', $response->json('data.0.tipo_declaracion'));
    }

    /**
     * Test: Filtrar por periodo fiscal
     */
    public function test_puede_filtrar_por_periodo(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        DeclaracionTributaria::factory()->create([
            'empresa_id' => $empresa->id,
            'periodo_fiscal' => '2025-01',
        ]);
        DeclaracionTributaria::factory()->create([
            'empresa_id' => $empresa->id,
            'periodo_fiscal' => '2025-02',
        ]);

        $response = $this->authenticatedJson('GET', '/api/declaraciones-tributarias?periodo=2025-01', [], $usuario);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test: Actualizar estado de declaración
     */
    public function test_puede_actualizar_estado_declaracion(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $declaracion = DeclaracionTributaria::factory()->create([
            'empresa_id' => $empresa->id,
            'estado' => 'borrador',
        ]);

        $response = $this->authenticatedJson('PATCH', "/api/declaraciones-tributarias/{$declaracion->id}", [
            'estado' => 'enviada',
        ], $usuario);

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'enviada');

        $this->assertDatabaseHas('declaraciones_tributarias', [
            'id' => $declaracion->id,
            'estado' => 'enviada',
        ]);
    }
}
