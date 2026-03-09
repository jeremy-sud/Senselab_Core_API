<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\PeriodoNomina;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PeriodoNominaTest extends TestCase
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

    private function datosPeriodoValido(array $overrides = []): array
    {
        return array_merge([
            'nombre_periodo' => 'Enero 2026',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-01-31',
            'fecha_pago' => '2026-02-05',
            'estado' => 'Abierto',
        ], $overrides);
    }

    private function crearPeriodo(array $overrides = []): PeriodoNomina
    {
        return PeriodoNomina::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nombre_periodo' => 'Periodo Test',
            'fecha_inicio' => '2026-03-01',
            'fecha_fin' => '2026-03-31',
            'estado' => 'Abierto',
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_periodos(): void
    {
        $this->crearPeriodo();

        $response = $this->authenticatedJson('GET', '/api/periodos-nomina', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_periodo(): void
    {
        $response = $this->authenticatedJson('POST', '/api/periodos-nomina', $this->datosPeriodoValido(), $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_periodo(): void
    {
        $periodo = $this->crearPeriodo();

        $response = $this->authenticatedJson('GET', "/api/periodos-nomina/{$periodo->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_periodo_abierto(): void
    {
        $periodo = $this->crearPeriodo();

        $response = $this->authenticatedJson('PUT', "/api/periodos-nomina/{$periodo->id}", [
            'nombre_periodo' => 'Periodo Actualizado',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_periodo_sin_pagos(): void
    {
        $periodo = $this->crearPeriodo();

        $response = $this->authenticatedJson('DELETE', "/api/periodos-nomina/{$periodo->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/periodos-nomina', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre_periodo', 'fecha_inicio', 'fecha_fin']);
    }

    #[Test]
    public function validacion_fecha_fin_posterior_a_inicio(): void
    {
        $datos = $this->datosPeriodoValido([
            'fecha_inicio' => '2026-03-31',
            'fecha_fin' => '2026-03-01',
        ]);

        $response = $this->authenticatedJson('POST', '/api/periodos-nomina', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_fin']);
    }

    #[Test]
    public function puede_cerrar_periodo(): void
    {
        $periodo = $this->crearPeriodo();

        $response = $this->authenticatedJson('POST', "/api/periodos-nomina/{$periodo->id}/cerrar", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/periodos-nomina');

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_listar_periodos_activos(): void
    {
        $this->crearPeriodo();

        $response = $this->authenticatedJson('GET', '/api/periodos-nomina/activos/list', [], $this->usuario);

        $response->assertOk();
    }
}
