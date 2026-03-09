<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Models\Venta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CuentaPorCobrarTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;

        $this->cliente = Cliente::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '01',
            'numero_identificacion' => '123456789',
            'nombre' => 'Cliente',
            'apellidos' => 'Test',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    private function datosCuentaValida(array $overrides = []): array
    {
        return array_merge([
            'cliente_id' => $this->cliente->id,
            'numero_documento' => 'FAC-' . rand(1000, 9999),
            'fecha_emision' => '2026-01-01',
            'fecha_vencimiento' => '2026-02-01',
            'moneda' => 'CRC',
            'monto_original' => 500000.00,
            'estado' => 'Pendiente',
        ], $overrides);
    }

    private function crearCuenta(array $overrides = []): CuentaPorCobrar
    {
        $cuenta = new CuentaPorCobrar(array_merge([
            'cliente_id' => $this->cliente->id,
            'numero_documento' => 'FAC-' . rand(1000, 9999),
            'fecha_emision' => '2026-01-01',
            'fecha_vencimiento' => '2026-02-01',
            'moneda' => 'CRC',
            'monto_original' => 500000.00,
            'monto_pagado' => 0,
            'monto_pendiente' => 500000.00,
            'estado' => 'Pendiente',
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
        $cuenta->empresa_id = $this->empresa->id;
        $cuenta->save();

        return $cuenta;
    }

    #[Test]
    public function puede_listar_cuentas_por_cobrar(): void
    {
        $this->crearCuenta();

        $response = $this->authenticatedJson('GET', '/api/cuentas-por-cobrar', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_cuenta_por_cobrar(): void
    {
        $response = $this->authenticatedJson('POST', '/api/cuentas-por-cobrar', $this->datosCuentaValida(), $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_cuenta_por_cobrar(): void
    {
        $cuenta = $this->crearCuenta();

        $response = $this->authenticatedJson('GET', "/api/cuentas-por-cobrar/{$cuenta->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_cuenta_por_cobrar(): void
    {
        $cuenta = $this->crearCuenta();

        $response = $this->authenticatedJson('PUT', "/api/cuentas-por-cobrar/{$cuenta->id}", [
            'observaciones' => 'Actualización de observaciones',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_cuenta_por_cobrar(): void
    {
        $cuenta = $this->crearCuenta();

        $response = $this->authenticatedJson('DELETE', "/api/cuentas-por-cobrar/{$cuenta->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/cuentas-por-cobrar', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cliente_id', 'numero_documento', 'fecha_emision', 'fecha_vencimiento', 'monto_original']);
    }

    #[Test]
    public function validacion_fecha_vencimiento_posterior_a_emision(): void
    {
        $datos = $this->datosCuentaValida([
            'fecha_emision' => '2026-03-01',
            'fecha_vencimiento' => '2026-01-01',
        ]);

        $response = $this->authenticatedJson('POST', '/api/cuentas-por-cobrar', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_vencimiento']);
    }

    #[Test]
    public function validacion_moneda_valida(): void
    {
        $datos = $this->datosCuentaValida(['moneda' => 'XXX']);

        $response = $this->authenticatedJson('POST', '/api/cuentas-por-cobrar', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['moneda']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/cuentas-por-cobrar');

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_listar_cuentas_vencidas(): void
    {
        $this->crearCuenta([
            'fecha_vencimiento' => '2025-01-01',
            'estado' => 'Vencida',
        ]);

        $response = $this->authenticatedJson('GET', '/api/cuentas-por-cobrar/vencidas/list', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_ver_resumen_por_estado(): void
    {
        $this->crearCuenta(['estado' => 'Pendiente']);

        $response = $this->authenticatedJson('GET', '/api/cuentas-por-cobrar/resumen/por-estado', [], $this->usuario);

        $response->assertOk();
    }

}
