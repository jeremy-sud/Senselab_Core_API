<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\TipoImpuesto;
use App\Models\TasaImpuesto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TipoImpuestoTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;
    }

    private function crearTipoImpuesto(array $overrides = []): TipoImpuesto
    {
        return TipoImpuesto::create(array_merge([
            'codigo_hacienda' => 'IVA' . uniqid(),
            'nombre' => 'IVA Test ' . uniqid(),
            'descripcion' => 'Impuesto sobre valor agregado',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_tipos_impuesto(): void
    {
        $this->crearTipoImpuesto(['nombre' => 'IVA', 'codigo_hacienda' => 'IVA01']);
        $this->crearTipoImpuesto(['nombre' => 'ISC', 'codigo_hacienda' => 'ISC01']);

        $response = $this->authenticatedJson('GET', '/api/tipos-impuesto', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_tipo_impuesto(): void
    {
        $data = [
            'codigo_hacienda' => 'IVA13',
            'nombre' => 'IVA 13%',
            'descripcion' => 'Impuesto al valor agregado 13%',
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/tipos-impuesto', $data, $this->usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tipos_impuesto', ['nombre' => 'IVA 13%']);
    }

    #[Test]
    public function puede_ver_tipo_impuesto(): void
    {
        $tipo = $this->crearTipoImpuesto();

        $response = $this->authenticatedJson('GET', "/api/tipos-impuesto/{$tipo->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_tipo_impuesto(): void
    {
        $tipo = $this->crearTipoImpuesto();

        $response = $this->authenticatedJson('PUT', "/api/tipos-impuesto/{$tipo->id}", [
            'nombre' => 'IVA Actualizado',
            'descripcion' => 'Descripción actualizada',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_tipo_impuesto(): void
    {
        $tipo = $this->crearTipoImpuesto();

        $response = $this->authenticatedJson('DELETE', "/api/tipos-impuesto/{$tipo->id}", [], $this->usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function puede_listar_activos(): void
    {
        $this->crearTipoImpuesto(['activo' => true]);
        $this->crearTipoImpuesto(['activo' => false]);

        $response = $this->authenticatedJson('GET', '/api/tipos-impuesto/activos/list', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_crear_tasa_impuesto(): void
    {
        $tipo = $this->crearTipoImpuesto();

        $data = [
            'tipo_impuesto_id' => $tipo->id,
            'tasa_porcentaje' => 13.00,
            'fecha_inicio_vigencia' => '2025-01-01',
            'descripcion' => 'Tasa IVA general',
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/tasas-impuesto', $data, $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_listar_tasas_impuesto(): void
    {
        $tipo = $this->crearTipoImpuesto();
        TasaImpuesto::create([
            'tipo_impuesto_id' => $tipo->id,
            'tasa_porcentaje' => 13.00,
            'fecha_inicio_vigencia' => '2025-01-01',
            'activo' => true,
        ]);

        $response = $this->authenticatedJson('GET', '/api/tasas-impuesto', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/tipos-impuesto');

        $response->assertUnauthorized();
    }
}
