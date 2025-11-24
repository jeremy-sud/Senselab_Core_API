<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MovimientoBancario;
use App\Models\CuentaBancaria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
class MovimientoBancarioTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test: Crear movimiento bancario válido
     */
    public function test_puede_crear_movimiento_bancario(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $cuenta = CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        $data = [
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'fecha_movimiento' => '2025-01-15',
            'tipo_movimiento' => 'deposito',
            'monto' => 50000.00,
            'descripcion' => 'Depósito de prueba',
            'conciliado' => false,
        ];

        $response = $this->authenticatedJson('POST', '/api/movimientos-bancarios', $data, $usuario);

        $response->assertStatus(201)
            ->assertJsonPath('data.tipo_movimiento', 'deposito')
            ->assertJsonPath('data.monto', 50000);

        $this->assertDatabaseHas('movimientos_bancarios', [
            'cuenta_bancaria_id' => $cuenta->id,
            'monto' => 50000.00,
        ]);
    }

    /**
     * Test: Validar monto no puede ser cero
     */
    public function test_valida_monto_no_puede_ser_cero(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $cuenta = CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        $data = [
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'fecha_movimiento' => '2025-01-15',
            'tipo_movimiento' => 'deposito',
            'monto' => 0, // Monto inválido
            'descripcion' => 'Test',
        ];

        $response = $this->authenticatedJson('POST', '/api/movimientos-bancarios', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['monto']);
    }

    /**
     * Test: Validar fecha conciliación required si conciliado es true
     */
    public function test_valida_fecha_conciliacion_required_if_conciliado(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $cuenta = CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        $data = [
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'fecha_movimiento' => '2025-01-15',
            'tipo_movimiento' => 'deposito',
            'monto' => 10000.00,
            'conciliado' => true,
            // Falta fecha_conciliacion
        ];

        $response = $this->authenticatedJson('POST', '/api/movimientos-bancarios', $data, $usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_conciliacion']);
    }

    /**
     * Test: Filtrar por tipo de movimiento
     */
    public function test_puede_filtrar_por_tipo_movimiento(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $cuenta = CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        MovimientoBancario::factory()->create([
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'tipo_movimiento' => 'deposito',
        ]);
        MovimientoBancario::factory()->create([
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'tipo_movimiento' => 'retiro',
        ]);

        $response = $this->authenticatedJson('GET', '/api/movimientos-bancarios?tipo_movimiento=deposito', [], $usuario);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('deposito', $response->json('data.0.tipo_movimiento'));
    }

    /**
     * Test: Filtrar por estado de conciliación
     */
    public function test_puede_filtrar_por_conciliado(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $cuenta = CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        MovimientoBancario::factory()->create([
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'conciliado' => true,
        ]);
        MovimientoBancario::factory()->create([
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'conciliado' => false,
        ]);

        $response = $this->authenticatedJson('GET', '/api/movimientos-bancarios?conciliado=0', [], $usuario);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /**
     * Test: Filtrar por rango de fechas
     */
    public function test_puede_filtrar_por_rango_fechas(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $cuenta = CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        MovimientoBancario::factory()->create([
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'fecha_movimiento' => '2025-01-10',
        ]);
        MovimientoBancario::factory()->create([
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'fecha_movimiento' => '2025-01-20',
        ]);
        MovimientoBancario::factory()->create([
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'fecha_movimiento' => '2025-02-05',
        ]);

        $response = $this->authenticatedJson(
            'GET',
            '/api/movimientos-bancarios?fecha_desde=2025-01-01&fecha_hasta=2025-01-31',
            [],
            $usuario
        );

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test: Búsqueda por número de referencia
     */
    public function test_puede_buscar_por_numero_referencia(): void
    {
        $this->seedRoles();
        $this->seedPermisos();
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;

        $cuenta = CuentaBancaria::factory()->create([
            'empresa_id' => $empresa->id,
        ]);

        MovimientoBancario::factory()->create([
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'numero_referencia' => 'REF-2025-001',
        ]);
        MovimientoBancario::factory()->create([
            'cuenta_bancaria_id' => $cuenta->id,
            'empresa_id' => $empresa->id,
            'numero_referencia' => 'REF-2025-002',
        ]);

        $response = $this->authenticatedJson('GET', '/api/movimientos-bancarios?search=REF-2025-001', [], $usuario);

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }
}
