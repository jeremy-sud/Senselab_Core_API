<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;
use App\Models\ZonaGeografica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Tests para ZonaGeograficaController (Sprint 2)
 * 
 * Verifica:
 * - CRUD completo
 * - Jerarquías (provincia → canton → distrito)
 * - Multi-tenancy automático
 * - Filtros por tipo y zona padre
 */
class ZonaGeograficaTest extends TestCase
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

        $permisos = ['zonas-geograficas.leer', 'zonas-geograficas.crear', 'zonas-geograficas.editar', 'zonas-geograficas.eliminar'];
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

        $this->usuario->roles()->attach($this->rol->id, ['activo' => true, 'eliminado' => false]);
    }

    public function test_puede_crear_zona_con_multi_tenancy_automatico(): void
    {
        Sanctum::actingAs($this->usuario);

        $data = [
            'nombre' => 'San José',
            'tipo' => 'provincia',
            'codigo' => '01',
        ];

        $response = $this->postJson('/api/zonas-geograficas', $data);

        // Debug: mostrar respuesta si falla
        if ($response->status() !== 201) {
            dump($response->json());
        }

        $response->assertStatus(201);
        // Verificar que se creó y que el controller asignó empresa_id si el modelo usa BelongsToTenant
        $this->assertDatabaseCount('zonas_geograficas', 1);
        $zona = \App\Models\ZonaGeografica::withoutGlobalScopes()->first();
        $this->assertEquals('San José', $zona->nombre);
        // El multi-tenancy puede ser aplicado por el trait BelongsToTenant o el controller
        $this->assertNotNull($zona->empresa_id);
    }

    public function test_puede_crear_jerarquia_provincia_canton_distrito(): void
    {
        Sanctum::actingAs($this->usuario);

        // Crear provincia
        $provincia = ZonaGeografica::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Heredia',
            'tipo' => 'provincia',
            'codigo' => '04',
            'activo' => true,
            'eliminado' => false,
        ]);

        // Crear cantón
        $canton = ZonaGeografica::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Heredia',
            'tipo' => 'canton',
            'codigo' => '0401',
            'zona_padre_id' => $provincia->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        // Crear distrito
        $distrito = ZonaGeografica::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Ulloa',
            'tipo' => 'distrito',
            'codigo' => '040104',
            'zona_padre_id' => $canton->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->assertDatabaseHas('zonas_geograficas', ['id' => $provincia->id, 'zona_padre_id' => null]);
        $this->assertDatabaseHas('zonas_geograficas', ['id' => $canton->id, 'zona_padre_id' => $provincia->id]);
        $this->assertDatabaseHas('zonas_geograficas', ['id' => $distrito->id, 'zona_padre_id' => $canton->id]);
    }

    public function test_filtro_por_tipo_funciona(): void
    {
        Sanctum::actingAs($this->usuario);

        ZonaGeografica::create(['empresa_id' => $this->empresa->id, 'codigo' => '02', 'nombre' => 'Alajuela', 'tipo' => 'provincia', 'activo' => true, 'eliminado' => false]);
        ZonaGeografica::create(['empresa_id' => $this->empresa->id, 'codigo' => 'R01', 'nombre' => 'Ruta 1', 'tipo' => 'ruta', 'activo' => true, 'eliminado' => false]);

        $response = $this->getJson('/api/zonas-geograficas?tipo=provincia');

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals('provincia', $item['tipo']);
        }
    }

    public function test_filtro_por_zona_padre_funciona(): void
    {
        Sanctum::actingAs($this->usuario);

        $padre = ZonaGeografica::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => '03',
            'nombre' => 'Cartago',
            'tipo' => 'provincia',
            'activo' => true,
            'eliminado' => false,
        ]);

        ZonaGeografica::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => '0301',
            'nombre' => 'Cartago Centro',
            'tipo' => 'canton',
            'zona_padre_id' => $padre->id,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->getJson("/api/zonas-geograficas?zona_padre_id={$padre->id}");

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $item) {
            $this->assertEquals($padre->id, $item['zona_padre_id']);
        }
    }

    public function test_eager_loading_incluye_relaciones(): void
    {
        Sanctum::actingAs($this->usuario);

        $padre = ZonaGeografica::create([
            'empresa_id' => $this->empresa->id,
            'codigo' => '05',
            'nombre' => 'Guanacaste',
            'tipo' => 'provincia',
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->getJson("/api/zonas-geograficas/{$padre->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'nombre',
                'tipo',
                'empresa',
                'zona_padre',
                'vendedor_asignado',
            ]
        ]);
    }

    public function test_validacion_tipo_debe_ser_valido(): void
    {
        Sanctum::actingAs($this->usuario);

        $response = $this->postJson('/api/zonas-geograficas', [
            'nombre' => 'Test',
            'tipo' => 'tipo-invalido',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tipo']);
    }
}
