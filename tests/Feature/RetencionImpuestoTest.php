<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\RetencionImpuesto;
use App\Models\Proveedor;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RetencionImpuestoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear retención de impuesto válida
     */
    public function test_puede_crear_retencion_valida(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $proveedor = Proveedor::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        $data = [
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'tipo_retencion' => 'renta',
            'porcentaje_retencion' => 2.00,
            'monto_base' => 100000.00,
            'monto_retenido' => 2000.00,
            'fecha_retencion' => '2025-01-15',
            'periodo_declaracion' => '2025-01',
            'declarado' => false,
        ];

        $response = $this->authenticatedJson('POST', '/api/retenciones-impuesto', $data, $usuario);

        $response->assertStatus(201)
            ->assertJsonPath('data.tipo_retencion', 'renta')
            ->assertJsonPath('data.porcentaje_retencion', 2)
            ->assertJsonPath('data.monto_retenido', 2000);

        $this->assertDatabaseHas('retenciones_impuesto', [
            'empresa_id' => $empresa->id,
            'tipo_retencion' => 'renta',
            'monto_retenido' => 2000.00,
        ]);
    }

    /**
     * Test: Validar porcentaje retención rango 0-100
     */
    public function test_valida_porcentaje_retencion_rango(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $proveedor = Proveedor::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        $data = [
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'tipo_retencion' => 'renta',
            'porcentaje_retencion' => 150.00, // Inválido (>100)
            'monto_base' => 100000.00,
            'monto_retenido' => 150000.00,
            'fecha_retencion' => '2025-01-15',
            'periodo_declaracion' => '2025-01',
        ];

        $response = $this->authenticatedJson('POST', '/api/retenciones-impuesto', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['porcentaje_retencion']);
    }

    /**
     * Test: Validar periodo declaración formato YYYY-MM
     */
    public function test_valida_periodo_declaracion_formato(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $proveedor = Proveedor::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        $data = [
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'tipo_retencion' => 'renta',
            'porcentaje_retencion' => 2.00,
            'monto_base' => 100000.00,
            'monto_retenido' => 2000.00,
            'fecha_retencion' => '2025-01-15',
            'periodo_declaracion' => '01/2025', // Formato inválido
        ];

        $response = $this->authenticatedJson('POST', '/api/retenciones-impuesto', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['periodo_declaracion']);
    }

    /**
     * Test: Filtrar por tipo de retención
     */
    public function test_puede_filtrar_por_tipo_retencion(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $proveedor = Proveedor::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        RetencionImpuesto::factory()->create([
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'tipo_retencion' => 'renta',
        ]);
        RetencionImpuesto::factory()->create([
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'tipo_retencion' => 'iva',
        ]);

        $response = $this->authenticatedJson('GET', '/api/retenciones-impuesto?tipo_retencion=renta', [], $usuario);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('renta', $response->json('data.0.tipo_retencion'));
    }

    /**
     * Test: Filtrar por periodo
     */
    public function test_puede_filtrar_por_periodo(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $proveedor = Proveedor::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        RetencionImpuesto::factory()->create([
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'periodo_declaracion' => '2025-01',
        ]);
        RetencionImpuesto::factory()->create([
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'periodo_declaracion' => '2025-02',
        ]);

        $response = $this->authenticatedJson('GET', '/api/retenciones-impuesto?periodo=2025-01', [], $usuario);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test: Validar tipos de retención permitidos
     */
    public function test_valida_tipos_retencion_permitidos(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $proveedor = Proveedor::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        $data = [
            'empresa_id' => $empresa->id,
            'proveedor_id' => $proveedor->id,
            'tipo_retencion' => 'invalido', // Tipo no permitido
            'porcentaje_retencion' => 2.00,
            'monto_base' => 100000.00,
            'monto_retenido' => 2000.00,
            'fecha_retencion' => '2025-01-15',
            'periodo_declaracion' => '2025-01',
        ];

        $response = $this->authenticatedJson('POST', '/api/retenciones-impuesto', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_retencion']);
    }
}
