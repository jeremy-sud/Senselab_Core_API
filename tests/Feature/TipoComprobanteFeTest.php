<?php

namespace Tests\Feature;

use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\TipoComprobanteFe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests para TipoComprobanteFeController (Sprint 2)
 * 
 * Verifica:
 * - Catálogo de comprobantes DGT
 * - Filtros por código DGT y características
 * - Códigos válidos: 01-Factura, 02-Débito, 03-Crédito, 04-Tiquete
 */
class TipoComprobanteFeTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;
    private Rol $rol;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rol = Rol::create([
            'nombre' => 'Admin',
            'descripcion' => 'Administrador',
            'activo' => true,
            'eliminado' => false,
        ]);

        $permisos = ['tipos-comprobantes-fe.leer', 'tipos-comprobantes-fe.crear', 'tipos-comprobantes-fe.editar'];
        foreach ($permisos as $slug) {
            $permiso = Permiso::create([
                'nombre' => str_replace('-', '.', $slug),
                'slug' => $slug,
                'descripcion' => 'Permiso ' . $slug,
            ]);
            $this->rol->permisos()->attach($permiso->id, ['activo' => true]);
        }

        // Crear empresa para multi-tenancy
        $empresa = \App\Models\Empresa::create([
            'nombre' => 'Empresa Test',
            'nombre_comercial' => 'Test',
            'razon_social' => 'Test S.A.',
            'num_identificacion_dgt' => '1234567890',
            'tipo_identificacion' => '02',
            'email' => 'empresa@test.com',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->usuario = Usuario::create([
            'empresa_id' => $empresa->id,
            'email' => 'admin@test.com',
            'nombre' => 'Admin',
            'apellidos' => 'Test',
            'password_hash' => bcrypt('password'),
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->usuario->roles()->attach($this->rol->id, ['activo' => true, 'eliminado' => false]);
    }

    public function test_puede_listar_tipos_comprobante(): void
    {
        Sanctum::actingAs($this->usuario);

        TipoComprobanteFe::create([
            'nombre' => 'Factura Electrónica',
            'codigo_dgt' => '01',
            'descripcion' => 'Factura electrónica',
            'requiere_referencia' => false,
            'permite_exportacion' => true,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->getJson('/api/tipos-comprobantes-fe');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nombre', 'codigo_dgt']
            ]
        ]);
    }

    public function test_codigos_dgt_validos(): void
    {
        Sanctum::actingAs($this->usuario);

        $codigosValidos = ['01', '02', '03', '04'];
        
        foreach ($codigosValidos as $codigo) {
            $response = $this->postJson('/api/tipos-comprobantes-fe', [
                'nombre' => "Tipo {$codigo}",
                'codigo_dgt' => $codigo,
                'descripcion' => 'Descripción',
            ]);

            $response->assertStatus(201);
        }

        $this->assertDatabaseCount('tipos_comprobantes_fe', 4);
    }

    public function test_filtro_por_requiere_referencia(): void
    {
        Sanctum::actingAs($this->usuario);

        TipoComprobanteFe::create([
            'nombre' => 'Nota Débito',
            'codigo_dgt' => '02',
            'requiere_referencia' => true,
            'activo' => true,
            'eliminado' => false,
        ]);

        TipoComprobanteFe::create([
            'nombre' => 'Factura',
            'codigo_dgt' => '01',
            'requiere_referencia' => false,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->getJson('/api/tipos-comprobantes-fe?requiere_referencia=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertTrue($item['requiere_referencia']);
        }
    }

    public function test_filtro_por_permite_exportacion(): void
    {
        Sanctum::actingAs($this->usuario);

        TipoComprobanteFe::create([
            'nombre' => 'Factura Export',
            'codigo_dgt' => '01',
            'permite_exportacion' => true,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->getJson('/api/tipos-comprobantes-fe?permite_exportacion=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertTrue($item['permite_exportacion']);
        }
    }

    public function test_filtro_por_codigo_dgt(): void
    {
        Sanctum::actingAs($this->usuario);

        TipoComprobanteFe::create(['nombre' => 'Factura', 'codigo_dgt' => '01', 'descripcion' => 'Factura electrónica', 'activo' => true, 'eliminado' => false]);
        TipoComprobanteFe::create(['nombre' => 'Tiquete', 'codigo_dgt' => '04', 'descripcion' => 'Tiquete electrónico', 'activo' => true, 'eliminado' => false]);

        $response = $this->getJson('/api/tipos-comprobantes-fe?codigo_dgt=01');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data); // Solo debe devolver el de código '01'
        $this->assertEquals('01', $data[0]['codigo_dgt']);
    }

    public function test_ordenamiento_por_codigo_dgt(): void
    {
        Sanctum::actingAs($this->usuario);

        TipoComprobanteFe::create(['nombre' => 'Tiquete', 'codigo_dgt' => '04', 'activo' => true, 'eliminado' => false]);
        TipoComprobanteFe::create(['nombre' => 'Factura', 'codigo_dgt' => '01', 'activo' => true, 'eliminado' => false]);
        TipoComprobanteFe::create(['nombre' => 'Nota Débito', 'codigo_dgt' => '02', 'activo' => true, 'eliminado' => false]);

        $response = $this->getJson('/api/tipos-comprobantes-fe');

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Verificar que estén ordenados por codigo_dgt ASC
        $this->assertEquals('01', $data[0]['codigo_dgt']);
        $this->assertEquals('02', $data[1]['codigo_dgt']);
        $this->assertEquals('04', $data[2]['codigo_dgt']);
    }

    public function test_validacion_codigo_dgt_debe_tener_2_caracteres(): void
    {
        Sanctum::actingAs($this->usuario);

        $response = $this->postJson('/api/tipos-comprobantes-fe', [
            'nombre' => 'Test',
            'codigo_dgt' => '1', // Solo 1 carácter
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['codigo_dgt']);
    }
}
