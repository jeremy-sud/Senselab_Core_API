<?php

namespace Tests\Feature;

use App\Models\TasaImpuesto;
use App\Models\TipoImpuesto;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TasaImpuestoTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected TipoImpuesto $tipoImpuesto;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();

        $this->tipoImpuesto = TipoImpuesto::create([
            'codigo_hacienda' => '01',
            'nombre' => 'IVA',
            'descripcion' => 'Impuesto al valor agregado',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    private function datosTasaValida(array $overrides = []): array
    {
        return array_merge([
            'tipo_impuesto_id' => $this->tipoImpuesto->id,
            'tasa_porcentaje' => 13.00,
            'fecha_inicio_vigencia' => '2026-01-01',
            'descripcion' => 'Tasa IVA general',
        ], $overrides);
    }

    private function crearTasa(array $overrides = []): TasaImpuesto
    {
        return TasaImpuesto::create(array_merge([
            'tipo_impuesto_id' => $this->tipoImpuesto->id,
            'tasa_porcentaje' => 13.00,
            'fecha_inicio_vigencia' => '2026-01-01',
            'descripcion' => 'Tasa general',
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_tasas(): void
    {
        $this->crearTasa();

        $response = $this->authenticatedJson('GET', '/api/tasas-impuesto', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_tasa(): void
    {
        $response = $this->authenticatedJson('POST', '/api/tasas-impuesto', $this->datosTasaValida(), $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_tasa(): void
    {
        $tasa = $this->crearTasa();

        $response = $this->authenticatedJson('GET', "/api/tasas-impuesto/{$tasa->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_tasa(): void
    {
        $tasa = $this->crearTasa();

        $response = $this->authenticatedJson('PUT', "/api/tasas-impuesto/{$tasa->id}", [
            'tasa_porcentaje' => 15.00,
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_tasa(): void
    {
        $tasa = $this->crearTasa();

        $response = $this->authenticatedJson('DELETE', "/api/tasas-impuesto/{$tasa->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/tasas-impuesto', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_impuesto_id', 'tasa_porcentaje', 'fecha_inicio_vigencia']);
    }

    #[Test]
    public function validacion_porcentaje_maximo(): void
    {
        $datos = $this->datosTasaValida(['tasa_porcentaje' => 150]);

        $response = $this->authenticatedJson('POST', '/api/tasas-impuesto', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tasa_porcentaje']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/tasas-impuesto');

        $response->assertUnauthorized();
    }

}
