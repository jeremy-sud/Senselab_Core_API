<?php

namespace Tests\Feature;

use App\Models\CuentaContable;
use App\Models\DetallePresupuesto;
use App\Models\Empresa;
use App\Models\Presupuesto;
use App\Models\TipoCuenta;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetallePresupuestoTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected Presupuesto $presupuesto;
    protected CuentaContable $cuentaContable;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;

        $this->presupuesto = Presupuesto::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Presupuesto 2026',
            'periodo_inicio' => '2026-01-01',
            'periodo_fin' => '2026-12-31',
            'estado' => 'Activo',
            'activo' => true,
            'eliminado' => false,
        ]);

        $tipoCuenta = TipoCuenta::create([
            'nombre' => 'Activos',
            'descripcion' => 'Cuentas de activos',
            'naturaleza' => 'Deudora',
            'activo' => true,
            'eliminado' => false,
        ]);

        $this->cuentaContable = CuentaContable::create([
            'empresa_id' => $this->empresa->id,
            'tipo_cuenta_id' => $tipoCuenta->id,
            'codigo' => '1-01-01',
            'nombre' => 'Caja General',
            'nivel' => 3,
            'acepta_movimientos' => true,
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    private function datosDetalleValido(array $overrides = []): array
    {
        return array_merge([
            'presupuesto_id' => $this->presupuesto->id,
            'cuenta_contable_id' => $this->cuentaContable->id,
            'monto_presupuestado' => 1000000.00,
        ], $overrides);
    }

    private function crearDetalle(array $overrides = []): DetallePresupuesto
    {
        return DetallePresupuesto::create(array_merge([
            'presupuesto_id' => $this->presupuesto->id,
            'cuenta_contable_id' => $this->cuentaContable->id,
            'monto_presupuestado' => 1000000.00,
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_detalles_presupuesto(): void
    {
        $this->crearDetalle();

        $response = $this->authenticatedJson('GET', "/api/presupuestos/{$this->presupuesto->id}/detalles", [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_detalle_presupuesto(): void
    {
        $response = $this->authenticatedJson('POST', '/api/detalles-presupuestos', $this->datosDetalleValido(), $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_detalle_presupuesto(): void
    {
        $detalle = $this->crearDetalle();

        $response = $this->authenticatedJson('GET', "/api/detalles-presupuestos/{$detalle->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_detalle_presupuesto(): void
    {
        $detalle = $this->crearDetalle();

        $response = $this->authenticatedJson('PUT', "/api/detalles-presupuestos/{$detalle->id}", [
            'monto_presupuestado' => 2000000.00,
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_detalle_presupuesto(): void
    {
        $detalle = $this->crearDetalle();

        $response = $this->authenticatedJson('DELETE', "/api/detalles-presupuestos/{$detalle->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/detalles-presupuestos', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['presupuesto_id', 'cuenta_contable_id', 'monto_presupuestado']);
    }

    #[Test]
    public function validacion_monto_negativo(): void
    {
        $datos = $this->datosDetalleValido(['monto_presupuestado' => -100]);

        $response = $this->authenticatedJson('POST', '/api/detalles-presupuestos', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['monto_presupuestado']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson("/api/presupuestos/1/detalles");

        $response->assertUnauthorized();
    }
}
