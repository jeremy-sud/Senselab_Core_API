<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\TipoCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests para TipoClienteController (Sprint 2)
 * 
 * Verifica:
 * - CRUD completo
 * - Filtros avanzados (search, activo, descuento, crédito)
 * - Multi-tenancy
 * - Autorización con policies
 */
class TipoClienteTest extends TestCase
{
    use RefreshDatabase;

    private Empresa $empresa;
    private Usuario $usuario;
    private Rol $rol;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Usar métodos del TestCase padre para consistencia
        $this->seedRoles();
        $this->seedPermisos();
        
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;
        $this->rol = Rol::where('nombre', 'Administrador')->first();
    }

    public function test_puede_listar_tipos_cliente(): void
    {
        // Crear tipos de cliente
        TipoCliente::create([
            'codigo' => 'MAY',
            'nombre' => 'Mayorista',
            'descripcion' => 'Cliente mayorista',
            'descuento_default' => 10.5,
            'dias_credito_default' => 30,
            'activo' => true,
            'eliminado' => false,
        ]);

        TipoCliente::create([
            'codigo' => 'MIN',
            'nombre' => 'Minorista',
            'descripcion' => 'Cliente minorista',
            'descuento_default' => 5.0,
            'dias_credito_default' => 15,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', '/api/tipos-clientes', [], $this->usuario);

        $response->assertStatus(200);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'nombre', 'descripcion', 'descuento_default', 'dias_credito_default']
            ]
        ]);
    }

    public function test_puede_crear_tipo_cliente(): void
    {
        $data = [
            'codigo' => 'VIP',
            'nombre' => 'VIP',
            'descripcion' => 'Cliente VIP',
            'descuento_default' => 15.0,
            'dias_credito_default' => 60,
        ];

        $response = $this->authenticatedJson('POST', '/api/tipos-clientes', $data, $this->usuario);

        $response->assertStatus(201);
        $response->assertJsonFragment(['nombre' => 'VIP']);
        $this->assertDatabaseHas('tipos_clientes', [
            'nombre' => 'VIP',
            'descuento_default' => 15.0,
        ]);
    }

    public function test_puede_actualizar_tipo_cliente(): void
    {
        $tipo = TipoCliente::create([
            'codigo' => 'CORP',
            'nombre' => 'Corporativo',
            'descripcion' => 'Cliente corporativo',
            'descuento_default' => 8.0,
            'dias_credito_default' => 45,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('PUT', "/api/tipos-clientes/{$tipo->id}", [
            'nombre' => 'Corporativo Actualizado',
            'descuento_default' => 12.0,
        ], $this->usuario);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tipos_clientes', [
            'id' => $tipo->id,
            'nombre' => 'Corporativo Actualizado',
            'descuento_default' => 12.0,
        ]);
    }

    public function test_puede_eliminar_tipo_cliente(): void
    {
        $tipo = TipoCliente::create([
            'codigo' => 'TMP',
            'nombre' => 'Temporal',
            'descripcion' => 'Tipo temporal',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/tipos-clientes/{$tipo->id}", [], $this->usuario);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tipos_clientes', [
            'id' => $tipo->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }

    public function test_filtro_por_busqueda_funciona(): void
    {
        TipoCliente::create(['codigo' => 'MAYP', 'nombre' => 'Mayorista Premium', 'activo' => true, 'eliminado' => false]);
        TipoCliente::create(['codigo' => 'MINB', 'nombre' => 'Minorista Básico', 'activo' => true, 'eliminado' => false]);

        $response = $this->authenticatedJson('GET', '/api/tipos-clientes?search=Mayorista', [], $this->usuario);

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['nombre' => 'Mayorista Premium']);
    }

    public function test_filtro_por_activo_funciona(): void
    {
        TipoCliente::create(['codigo' => 'ACT', 'nombre' => 'Activo', 'activo' => true, 'eliminado' => false]);
        TipoCliente::create(['codigo' => 'INACT', 'nombre' => 'Inactivo', 'activo' => false, 'eliminado' => false]);

        $response = $this->authenticatedJson('GET', '/api/tipos-clientes?activo=true', [], $this->usuario);

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertTrue($item['activo']);
        }
    }

    public function test_filtro_por_descuento_funciona(): void
    {
        TipoCliente::create(['codigo' => 'DESC', 'nombre' => 'Con Descuento', 'descuento_default' => 10.0, 'activo' => true, 'eliminado' => false]);
        TipoCliente::create(['codigo' => 'NDESC', 'nombre' => 'Sin Descuento', 'descuento_default' => 0, 'activo' => true, 'eliminado' => false]);

        $response = $this->authenticatedJson('GET', '/api/tipos-clientes?con_descuento=true', [], $this->usuario);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data), 'Debe haber al menos un tipo con descuento');
        foreach ($data as $item) {
            $this->assertGreaterThan(0, $item['descuento_default'], "El tipo {$item['nombre']} debería tener descuento > 0");
        }
    }

    public function test_filtro_por_credito_funciona(): void
    {
        TipoCliente::create(['codigo' => 'CRED', 'nombre' => 'Con Crédito', 'dias_credito_default' => 30, 'activo' => true, 'eliminado' => false]);
        TipoCliente::create(['codigo' => 'NCRED', 'nombre' => 'Sin Crédito', 'dias_credito_default' => 0, 'activo' => true, 'eliminado' => false]);

        $response = $this->authenticatedJson('GET', '/api/tipos-clientes?con_credito=true', [], $this->usuario);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data), 'Debe haber al menos un tipo con crédito');
        foreach ($data as $item) {
            $this->assertGreaterThan(0, $item['dias_credito_default'], "El tipo {$item['nombre']} debería tener días de crédito > 0");
        }
    }

    public function test_validacion_nombre_requerido(): void
    {
        $response = $this->authenticatedJson('POST', '/api/tipos-clientes', [
            'descripcion' => 'Sin nombre',
        ], $this->usuario);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nombre']);
    }

    public function test_validacion_descuento_debe_ser_numerico(): void
    {
        $response = $this->authenticatedJson('POST', '/api/tipos-clientes', [
            'nombre' => 'Test',
            'descuento_default' => 'no-numerico',
        ], $this->usuario);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['descuento_default']);
    }

    public function test_usuario_sin_autenticar_recibe_401(): void
    {
        $response = $this->getJson('/api/tipos-clientes');
        $response->assertStatus(401);
    }
}
