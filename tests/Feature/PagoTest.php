<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\FormaPago;
use App\Models\Pago;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PagoTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected FormaPago $formaPago;
    protected Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;

        $this->formaPago = FormaPago::create([
            'codigo_dgt' => '01',
            'nombre' => 'Efectivo',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->proveedor = Proveedor::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '02',
            'numero_identificacion' => '3101999888',
            'nombre' => 'Proveedor Pago Test',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    private function datosPagoValido(array $overrides = []): array
    {
        return array_merge([
            'proveedor_id' => $this->proveedor->id,
            'forma_pago_id' => $this->formaPago->id,
            'fecha_pago' => '2026-01-15',
            'monto' => 50000.00,
            'moneda' => 'CRC',
            'estado' => 'Pendiente',
            'descripcion' => 'Pago de prueba',
        ], $overrides);
    }

    private function crearPago(array $overrides = []): Pago
    {
        return Pago::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'forma_pago_id' => $this->formaPago->id,
            'fecha_pago' => '2026-01-15',
            'monto' => 50000.00,
            'moneda' => 'CRC',
            'estado' => 'Pendiente',
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_pagos(): void
    {
        $this->crearPago();

        $response = $this->authenticatedJson('GET', '/api/pagos', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_pago_con_proveedor(): void
    {
        $response = $this->authenticatedJson('POST', '/api/pagos', $this->datosPagoValido(), $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_pago(): void
    {
        $pago = $this->crearPago();

        $response = $this->authenticatedJson('GET', "/api/pagos/{$pago->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_pago_pendiente(): void
    {
        $pago = $this->crearPago();

        $response = $this->authenticatedJson('PUT', "/api/pagos/{$pago->id}", [
            'descripcion' => 'Descripción actualizada',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_pago_pendiente(): void
    {
        $pago = $this->crearPago();

        $response = $this->authenticatedJson('DELETE', "/api/pagos/{$pago->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/pagos', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['forma_pago_id', 'fecha_pago', 'monto']);
    }

    #[Test]
    public function validacion_requiere_proveedor_o_cliente(): void
    {
        $datos = $this->datosPagoValido();
        unset($datos['proveedor_id']);

        $response = $this->authenticatedJson('POST', '/api/pagos', $datos, $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function validacion_moneda_invalida(): void
    {
        $datos = $this->datosPagoValido(['moneda' => 'XXX']);

        $response = $this->authenticatedJson('POST', '/api/pagos', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['moneda']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/pagos');

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_ver_resumen_por_forma_pago(): void
    {
        $this->crearPago();

        $response = $this->authenticatedJson('GET', '/api/pagos/resumen/por-forma-pago', [], $this->usuario);

        $response->assertOk();
    }
}
