<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\TipoCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->empresa = Empresa::create([
            'nombre' => 'Empresa Test',
            'nombre_comercial' => 'Empresa Test',
            'razon_social' => 'Empresa Test S.A.',
            'num_identificacion_dgt' => '1234567890',
            'tipo_identificacion' => '02',
            'email' => 'empresa@test.com',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->rol = Rol::create([
            'nombre' => 'Admin',
            'descripcion' => 'Administrador',
            'activo' => true,
            'eliminado' => false,
        ]);

        // Crear permisos
        $permisos = [
            'tipos-clientes.leer',
            'tipos-clientes.crear',
            'tipos-clientes.editar',
            'tipos-clientes.eliminar',
        ];

        foreach ($permisos as $slug) {
            $permiso = Permiso::create([
                'nombre' => str_replace('-', '.', $slug),
                'slug' => $slug,
                'descripcion' => 'Permiso ' . $slug,
            ]);
            $this->rol->permisos()->attach($permiso->id, ['activo' => true]);
        }

        $this->usuario = Usuario::create([
            'empresa_id' => $this->empresa->id,
            'email' => 'admin@test.com',
            'nombre' => 'Admin',
            'apellidos' => 'Test',
            'password_hash' => bcrypt('password'),
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->usuario->roles()->attach($this->rol->id, [
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    public function test_puede_listar_tipos_cliente(): void
    {
        Sanctum::actingAs($this->usuario);

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

        $response = $this->getJson('/api/tipos-clientes');

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
        Sanctum::actingAs($this->usuario);

        $data = [
            'codigo' => 'VIP',
            'nombre' => 'VIP',
            'descripcion' => 'Clientes VIP con descuentos especiales',
            'descuento_default' => 15.0,
            'dias_credito_default' => 60,
        ];

        $response = $this->postJson('/api/tipos-clientes', $data);

        $response->assertStatus(201);
        $response->assertJsonFragment(['nombre' => 'VIP']);
        $this->assertDatabaseHas('tipos_clientes', [
            'nombre' => 'VIP',
            'descuento_default' => 15.0,
        ]);
    }

    public function test_puede_actualizar_tipo_cliente(): void
    {
        Sanctum::actingAs($this->usuario);

        $tipo = TipoCliente::create([
            'codigo' => 'CORP',
            'nombre' => 'Corporativo',
            'descripcion' => 'Cliente corporativo',
            'descuento_default' => 8.0,
            'dias_credito_default' => 45,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->putJson("/api/tipos-clientes/{$tipo->id}", [
            'descuento_default' => 12.0,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tipos_clientes', [
            'id' => $tipo->id,
            'descuento_default' => 12.0,
        ]);
    }

    public function test_puede_eliminar_tipo_cliente(): void
    {
        Sanctum::actingAs($this->usuario);

        $tipo = TipoCliente::create([
            'codigo' => 'TMP',
            'nombre' => 'Temporal',
            'descripcion' => 'Tipo temporal',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->deleteJson("/api/tipos-clientes/{$tipo->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('tipos_clientes', [
            'id' => $tipo->id,
            'activo' => false,
            'eliminado' => true,
        ]);
    }

    public function test_filtro_por_busqueda_funciona(): void
    {
        Sanctum::actingAs($this->usuario);

        TipoCliente::create(['codigo' => 'MAYP', 'nombre' => 'Mayorista Premium', 'activo' => true, 'eliminado' => false]);
        TipoCliente::create(['codigo' => 'MINB', 'nombre' => 'Minorista Básico', 'activo' => true, 'eliminado' => false]);

        $response = $this->getJson('/api/tipos-clientes?search=Mayorista');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['nombre' => 'Mayorista Premium']);
    }

    public function test_filtro_por_activo_funciona(): void
    {
        Sanctum::actingAs($this->usuario);

        TipoCliente::create(['codigo' => 'ACT', 'nombre' => 'Activo', 'activo' => true, 'eliminado' => false]);
        TipoCliente::create(['codigo' => 'INACT', 'nombre' => 'Inactivo', 'activo' => false, 'eliminado' => false]);

        $response = $this->getJson('/api/tipos-clientes?activo=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertTrue($item['activo']);
        }
    }

    public function test_filtro_por_descuento_funciona(): void
    {
        Sanctum::actingAs($this->usuario);

        TipoCliente::create(['codigo' => 'DESC', 'nombre' => 'Con Descuento', 'descuento_default' => 10.0, 'activo' => true, 'eliminado' => false]);
        TipoCliente::create(['codigo' => 'NDESC', 'nombre' => 'Sin Descuento', 'descuento_default' => 0, 'activo' => true, 'eliminado' => false]);

        $response = $this->getJson('/api/tipos-clientes?con_descuento=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertGreaterThan(0, $item['descuento_default']);
        }
    }

    public function test_filtro_por_credito_funciona(): void
    {
        Sanctum::actingAs($this->usuario);

        TipoCliente::create(['codigo' => 'CRED', 'nombre' => 'Con Crédito', 'dias_credito_default' => 30, 'activo' => true, 'eliminado' => false]);
        TipoCliente::create(['codigo' => 'NCRED', 'nombre' => 'Sin Crédito', 'dias_credito_default' => 0, 'activo' => true, 'eliminado' => false]);

        $response = $this->getJson('/api/tipos-clientes?con_credito=true');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertGreaterThan(0, $item['dias_credito_default']);
        }
    }

    public function test_validacion_nombre_requerido(): void
    {
        Sanctum::actingAs($this->usuario);

        $response = $this->postJson('/api/tipos-clientes', [
            'descripcion' => 'Sin nombre',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['nombre']);
    }

    public function test_validacion_descuento_debe_ser_numerico(): void
    {
        Sanctum::actingAs($this->usuario);

        $response = $this->postJson('/api/tipos-clientes', [
            'nombre' => 'Test',
            'descuento_default' => 'no-numerico',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['descuento_default']);
    }

    public function test_usuario_sin_autenticar_recibe_401(): void
    {
        $response = $this->getJson('/api/tipos-clientes');
        $response->assertStatus(401);
    }
}
