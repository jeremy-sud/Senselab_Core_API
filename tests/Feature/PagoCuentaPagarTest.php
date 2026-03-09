<?php

namespace Tests\Feature;

use App\Models\CuentaPorPagar;
use App\Models\Empresa;
use App\Models\FormaPago;
use App\Models\PagoCuentaPagar;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PagoCuentaPagarTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected CuentaPorPagar $cuenta;
    protected FormaPago $formaPago;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;

        // Autenticar para que BelongsToTenant resuelva empresa_id
        $this->actingAs($this->usuario, 'sanctum');

        $proveedor = Proveedor::create([
            'tipo_identificacion' => 'juridica',
            'numero_identificacion' => '3101999999',
            'nombre' => 'Proveedor Test',
            'activo' => true,
        ]);

        $this->cuenta = CuentaPorPagar::create([
            'proveedor_id' => $proveedor->id,
            'numero_documento' => 'CXP-001',
            'fecha_emision' => now()->format('Y-m-d'),
            'fecha_vencimiento' => now()->addDays(30)->format('Y-m-d'),
            'moneda' => 'CRC',
            'monto_original' => 200000.00,
            'monto_pagado' => 0.00,
            'monto_pendiente' => 200000.00,
            'estado' => 'Pendiente',
            'activo' => true,
        ]);

        $this->formaPago = FormaPago::create([
            'codigo_dgt' => '02',
            'nombre' => 'Transferencia',
            'activo' => true,
        ]);
    }

    private function crearPago(array $overrides = []): PagoCuentaPagar
    {
        return PagoCuentaPagar::create(array_merge([
            'cuenta_por_pagar_id' => $this->cuenta->id,
            'forma_pago_id' => $this->formaPago->id,
            'fecha_pago' => now()->format('Y-m-d'),
            'monto_pago' => 50000.00,
            'moneda' => 'CRC',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_pagos(): void
    {
        $this->crearPago();

        $response = $this->authenticatedJson('GET', '/api/pagos-cuentas-pagar', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_pago(): void
    {
        $data = [
            'cuenta_por_pagar_id' => $this->cuenta->id,
            'forma_pago_id' => $this->formaPago->id,
            'fecha_pago' => now()->format('Y-m-d'),
            'monto_pago' => 100000.00,
            'moneda' => 'CRC',
            'observaciones' => 'Abono a factura',
        ];

        $response = $this->authenticatedJson('POST', '/api/pagos-cuentas-pagar', $data, $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_pago(): void
    {
        $pago = $this->crearPago();

        $response = $this->authenticatedJson('GET', "/api/pagos-cuentas-pagar/{$pago->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_cuenta_al_crear(): void
    {
        $response = $this->authenticatedJson('POST', '/api/pagos-cuentas-pagar', [
            'forma_pago_id' => $this->formaPago->id,
            'monto_pago' => 10000,
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        app('auth')->forgetGuards();

        $response = $this->getJson('/api/pagos-cuentas-pagar');

        $response->assertUnauthorized();
    }
}
