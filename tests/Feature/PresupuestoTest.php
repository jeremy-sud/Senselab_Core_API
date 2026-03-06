<?php

namespace Tests\Feature;

use App\Models\Presupuesto;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PresupuestoTest extends TestCase
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

    private function crearPresupuesto(array $overrides = []): Presupuesto
    {
        return Presupuesto::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Presupuesto Anual ' . now()->year,
            'periodo_inicio' => now()->startOfYear()->format('Y-m-d'),
            'periodo_fin' => now()->endOfYear()->format('Y-m-d'),
            'estado' => 'Borrador',
        ], $overrides));
    }

    #[Test]
    public function puede_listar_presupuestos()
    {
        $this->crearPresupuesto();
        $this->crearPresupuesto(['nombre' => 'Presupuesto Q1']);

        $response = $this->authenticatedJson('GET', '/api/presupuestos', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_crear_presupuesto()
    {
        $data = [
            'nombre' => 'Presupuesto Q2 2025',
            'periodo_inicio' => '2025-04-01',
            'periodo_fin' => '2025-06-30',
        ];

        $response = $this->authenticatedJson('POST', '/api/presupuestos', $data, $this->usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('presupuestos', [
            'nombre' => 'Presupuesto Q2 2025',
            'empresa_id' => $this->empresa->id,
            'estado' => 'Borrador',
        ]);
    }

    #[Test]
    public function puede_ver_presupuesto_especifico()
    {
        $presupuesto = $this->crearPresupuesto();

        $response = $this->authenticatedJson('GET', "/api/presupuestos/{$presupuesto->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_presupuesto()
    {
        $presupuesto = $this->crearPresupuesto();

        $response = $this->authenticatedJson('PUT', "/api/presupuestos/{$presupuesto->id}", [
            'nombre' => 'Presupuesto Actualizado',
            'periodo_inicio' => '2025-01-01',
            'periodo_fin' => '2025-12-31',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_presupuesto_en_borrador()
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Borrador']);

        $response = $this->authenticatedJson('DELETE', "/api/presupuestos/{$presupuesto->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_finalizar_presupuesto()
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Borrador']);

        $response = $this->authenticatedJson('POST', "/api/presupuestos/{$presupuesto->id}/finalizar", [], $this->usuario);

        $response->assertOk();
        $presupuesto->refresh();
        $this->assertEquals('Finalizado', $presupuesto->estado);
    }

    #[Test]
    public function no_puede_modificar_presupuesto_finalizado()
    {
        $presupuesto = $this->crearPresupuesto(['estado' => 'Finalizado']);

        $response = $this->authenticatedJson('PUT', "/api/presupuestos/{$presupuesto->id}", [
            'nombre' => 'Intento de cambio',
            'periodo_inicio' => '2025-01-01',
            'periodo_fin' => '2025-12-31',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function validacion_periodo_fin_despues_de_inicio()
    {
        $data = [
            'nombre' => 'Presupuesto Inválido',
            'periodo_inicio' => '2025-06-30',
            'periodo_fin' => '2025-01-01',
        ];

        $response = $this->authenticatedJson('POST', '/api/presupuestos', $data, $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function no_puede_acceder_sin_autenticacion()
    {
        $response = $this->getJson('/api/presupuestos');

        $response->assertUnauthorized();
    }
}
