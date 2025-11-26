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
        
        $this->seedRoles();
        $this->seedPermisos();
        
        $this->usuario = $this->createAdminUsuario();
        $this->rol = Rol::where('nombre', 'Administrador')->first();
    }

    public function test_puede_listar_tipos_comprobante(): void
    {
        TipoComprobanteFe::create([
            'nombre' => 'Factura Electrónica',
            'codigo_dgt' => '01',
            'descripcion' => 'Factura electrónica',
            'requiere_referencia' => false,
            'permite_exportacion' => true,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', '/api/tipos-comprobantes-fe', [], $this->usuario);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nombre', 'codigo_dgt']
            ]
        ]);
    }

    public function test_codigos_dgt_validos(): void
    {
        $codigosValidos = ['01', '02', '03', '04'];
        
        foreach ($codigosValidos as $codigo) {
            $response = $this->authenticatedJson('POST', '/api/tipos-comprobantes-fe', [
                'nombre' => "Tipo {$codigo}",
                'codigo_dgt' => $codigo,
                'descripcion' => 'Descripción',
            ], $this->usuario);

            $response->assertStatus(201);
        }

        $this->assertDatabaseCount('tipos_comprobantes_fe', 4);
    }

    public function test_filtro_por_requiere_referencia(): void
    {
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

        $response = $this->authenticatedJson('GET', '/api/tipos-comprobantes-fe?requiere_referencia=true', [], $this->usuario);

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertTrue($item['requiere_referencia']);
        }
    }

    public function test_filtro_por_permite_exportacion(): void
    {
        TipoComprobanteFe::create([
            'nombre' => 'Factura Export',
            'codigo_dgt' => '01',
            'permite_exportacion' => true,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', '/api/tipos-comprobantes-fe?permite_exportacion=true', [], $this->usuario);

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertTrue($item['permite_exportacion']);
        }
    }

    public function test_filtro_por_codigo_dgt(): void
    {
        TipoComprobanteFe::create(['nombre' => 'Factura', 'codigo_dgt' => '01', 'descripcion' => 'Factura electrónica', 'activo' => true, 'eliminado' => false]);
        TipoComprobanteFe::create(['nombre' => 'Tiquete', 'codigo_dgt' => '04', 'descripcion' => 'Tiquete electrónico', 'activo' => true, 'eliminado' => false]);

        $response = $this->authenticatedJson('GET', '/api/tipos-comprobantes-fe?codigo_dgt=01', [], $this->usuario);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data); // Solo debe devolver el de código '01'
        $this->assertEquals('01', $data[0]['codigo_dgt']);
    }

    public function test_ordenamiento_por_codigo_dgt(): void
    {
        TipoComprobanteFe::create(['nombre' => 'Tiquete', 'codigo_dgt' => '04', 'activo' => true, 'eliminado' => false]);
        TipoComprobanteFe::create(['nombre' => 'Factura', 'codigo_dgt' => '01', 'activo' => true, 'eliminado' => false]);
        TipoComprobanteFe::create(['nombre' => 'Nota Débito', 'codigo_dgt' => '02', 'activo' => true, 'eliminado' => false]);

        $response = $this->authenticatedJson('GET', '/api/tipos-comprobantes-fe', [], $this->usuario);

        $response->assertStatus(200);
        $data = $response->json('data');
        
        // Verificar que estén ordenados por codigo_dgt ASC
        $this->assertEquals('01', $data[0]['codigo_dgt']);
        $this->assertEquals('02', $data[1]['codigo_dgt']);
        $this->assertEquals('04', $data[2]['codigo_dgt']);
    }

    public function test_validacion_codigo_dgt_debe_tener_2_caracteres(): void
    {
        $response = $this->authenticatedJson('POST', '/api/tipos-comprobantes-fe', [
            'nombre' => 'Test',
            'codigo_dgt' => '1', // Solo 1 carácter
        ], $this->usuario);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['codigo_dgt']);
    }
}
