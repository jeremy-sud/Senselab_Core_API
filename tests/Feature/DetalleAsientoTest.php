<?php

namespace Tests\Feature;

use App\Models\AsientoContable;
use App\Models\CuentaContable;
use App\Models\DetalleAsiento;
use App\Models\Empresa;
use App\Models\TipoCuenta;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DetalleAsientoTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected CuentaContable $cuentaContable;
    protected AsientoContable $asiento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;

        $tipoCuenta = TipoCuenta::create([
            'nombre' => 'Activo',
            'naturaleza' => 'Deudora',
            'activo' => true,
        ]);

        $this->cuentaContable = CuentaContable::create([
            'empresa_id' => $this->empresa->id,
            'tipo_cuenta_id' => $tipoCuenta->id,
            'codigo' => '1-01-001',
            'nombre' => 'Caja General',
            'naturaleza' => 'Deudora',
            'nivel' => 3,
            'acepta_movimientos' => true,
            'activo' => true,
        ]);

        $this->asiento = AsientoContable::create([
            'empresa_id' => $this->empresa->id,
            'numero_asiento' => 1,
            'fecha_asiento' => now()->format('Y-m-d'),
            'concepto' => 'Asiento de prueba',
            'estado' => 'Borrador',
            'usuario_id' => $this->usuario->id,
            'activo' => true,
        ]);
    }

    private function crearDetalle(array $overrides = []): DetalleAsiento
    {
        return DetalleAsiento::create(array_merge([
            'asiento_contable_id' => $this->asiento->id,
            'cuenta_contable_id' => $this->cuentaContable->id,
            'debe' => 1000.00,
            'haber' => 0.00,
            'descripcion' => 'Detalle de prueba',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_detalles_asiento(): void
    {
        $this->crearDetalle();

        $response = $this->authenticatedJson('GET', '/api/detalle-asientos', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_ver_detalle_asiento(): void
    {
        $detalle = $this->crearDetalle();

        $response = $this->authenticatedJson('GET', "/api/detalle-asientos/{$detalle->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_listar_detalles_por_cuenta(): void
    {
        $this->crearDetalle();

        $response = $this->authenticatedJson('GET', "/api/detalle-asientos/cuenta/{$this->cuentaContable->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_obtener_libro_mayor(): void
    {
        $this->crearDetalle();

        $response = $this->authenticatedJson('GET', '/api/detalle-asientos/reportes/libro-mayor', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_obtener_balance_comprobacion(): void
    {
        $this->crearDetalle();

        $response = $this->authenticatedJson('GET', '/api/detalle-asientos/reportes/balance-comprobacion', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_filtrar_por_asiento_contable(): void
    {
        $this->crearDetalle();

        $response = $this->authenticatedJson('GET', "/api/detalle-asientos?asiento_contable_id={$this->asiento->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/detalle-asientos');

        $response->assertUnauthorized();
    }
}
