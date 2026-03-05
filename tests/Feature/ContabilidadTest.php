<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\CuentaContable;
use App\Models\TipoCuenta;
use App\Models\AsientoContable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;

class ContabilidadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
    }

    /**
     * Crea un TipoCuenta de prueba (tabla global, sin empresa_id)
     */
    protected function createTipoCuenta(array $attrs = []): TipoCuenta
    {
        return TipoCuenta::create(array_merge([
            'nombre' => 'Activo',
            'descripcion' => 'Cuentas de activo',
            'naturaleza' => 'Deudora',
            'activo' => true,
            'eliminado' => false,
        ], $attrs));
    }

    // ========================================================================
    // TIPOS DE CUENTAS
    // ========================================================================

    #[Test]
    public function test_puede_listar_tipos_cuentas()
    {
        $usuario = $this->createAdminUsuario();

        $this->createTipoCuenta();

        $response = $this->authenticatedJson('GET', '/api/tipos-cuentas', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_tipo_cuenta()
    {
        $usuario = $this->createAdminUsuario();

        $data = [
            'nombre' => 'Pasivo',
            'descripcion' => 'Cuentas de pasivo',
            'naturaleza' => 'Acreedora',
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/tipos-cuentas', $data, $usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('tipos_cuentas', ['nombre' => 'Pasivo']);
    }

    // ========================================================================
    // CUENTAS CONTABLES
    // ========================================================================

    #[Test]
    public function test_puede_listar_cuentas_contables()
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/cuentas-contables', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_cuenta_contable()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $tipoCuenta = $this->createTipoCuenta();

        $data = [
            'nombre' => 'Caja General',
            'codigo' => '1.01.01',
            'descripcion' => 'Cuenta de caja general',
            'tipo_cuenta_id' => $tipoCuenta->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/cuentas-contables', $data, $usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('cuentas_contables', [
            'nombre' => 'Caja General',
            'codigo' => '1.01.01',
            'empresa_id' => $empresa->id,
        ]);
    }

    #[Test]
    public function test_puede_ver_cuenta_contable()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $tipoCuenta = $this->createTipoCuenta();

        $cuenta = CuentaContable::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Bancos',
            'codigo' => '1.01.02',
            'tipo_cuenta_id' => $tipoCuenta->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('GET', "/api/cuentas-contables/{$cuenta->id}", [], $usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_puede_actualizar_cuenta_contable()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $tipoCuenta = $this->createTipoCuenta();

        $cuenta = CuentaContable::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Cuenta Original',
            'codigo' => '1.01.03',
            'tipo_cuenta_id' => $tipoCuenta->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('PUT', "/api/cuentas-contables/{$cuenta->id}", [
            'nombre' => 'Cuenta Actualizada',
            'codigo' => '1.01.03',
        ], $usuario);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cuentas_contables', [
            'id' => $cuenta->id,
            'nombre' => 'Cuenta Actualizada',
        ]);
    }

    #[Test]
    public function test_puede_eliminar_cuenta_contable()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $tipoCuenta = $this->createTipoCuenta();

        $cuenta = CuentaContable::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Cuenta a Eliminar',
            'codigo' => '1.01.99',
            'tipo_cuenta_id' => $tipoCuenta->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('DELETE', "/api/cuentas-contables/{$cuenta->id}", [], $usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function test_validacion_cuenta_contable_sin_codigo()
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('POST', '/api/cuentas-contables', [
            'nombre' => 'Sin Código',
            // codigo omitido - es requerido
        ], $usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_no_permite_codigo_duplicado_misma_empresa()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $tipoCuenta = $this->createTipoCuenta();

        CuentaContable::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Cuenta Existente',
            'codigo' => '1.01.DUP',
            'tipo_cuenta_id' => $tipoCuenta->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        $response = $this->authenticatedJson('POST', '/api/cuentas-contables', [
            'nombre' => 'Cuenta Duplicada',
            'codigo' => '1.01.DUP',
            'tipo_cuenta_id' => $tipoCuenta->id,
        ], $usuario);

        $response->assertStatus(422);
    }

    // ========================================================================
    // ASIENTOS CONTABLES
    // ========================================================================

    #[Test]
    public function test_puede_listar_asientos_contables()
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/asientos-contables', [], $usuario);

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);
    }

    #[Test]
    public function test_puede_crear_asiento_contable_balanceado()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $tipoCuenta = $this->createTipoCuenta();

        $cuentaDebe = CuentaContable::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Caja',
            'codigo' => '1.01.01.ASI',
            'tipo_cuenta_id' => $tipoCuenta->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        $tipoCuentaPasivo = $this->createTipoCuenta([
            'nombre' => 'Pasivo',
            'naturaleza' => 'Acreedora',
        ]);

        $cuentaHaber = CuentaContable::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Proveedores',
            'codigo' => '2.01.01.ASI',
            'tipo_cuenta_id' => $tipoCuentaPasivo->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        $data = [
            'fecha_asiento' => now()->format('Y-m-d'),
            'concepto' => 'Asiento de prueba',
            'estado' => 'Borrador',
            'detalles' => [
                [
                    'cuenta_contable_id' => $cuentaDebe->id,
                    'debe' => 50000,
                    'haber' => 0,
                    'descripcion' => 'Débito a caja',
                ],
                [
                    'cuenta_contable_id' => $cuentaHaber->id,
                    'debe' => 0,
                    'haber' => 50000,
                    'descripcion' => 'Crédito a proveedores',
                ],
            ],
        ];

        $response = $this->authenticatedJson('POST', '/api/asientos-contables', $data, $usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function test_no_permite_asiento_desbalanceado()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $tipoCuenta = $this->createTipoCuenta();

        $cuenta1 = CuentaContable::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Cuenta Desbal 1',
            'codigo' => '1.DES.01',
            'tipo_cuenta_id' => $tipoCuenta->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        $cuenta2 = CuentaContable::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Cuenta Desbal 2',
            'codigo' => '2.DES.02',
            'tipo_cuenta_id' => $tipoCuenta->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        $data = [
            'fecha_asiento' => now()->format('Y-m-d'),
            'concepto' => 'Asiento desbalanceado',
            'detalles' => [
                [
                    'cuenta_contable_id' => $cuenta1->id,
                    'debe' => 50000,
                    'haber' => 0,
                ],
                [
                    'cuenta_contable_id' => $cuenta2->id,
                    'debe' => 0,
                    'haber' => 30000, // Desbalanceado: 50000 != 30000
                ],
            ],
        ];

        $response = $this->authenticatedJson('POST', '/api/asientos-contables', $data, $usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_asiento_requiere_minimo_dos_detalles()
    {
        $usuario = $this->createAdminUsuario();
        $empresa = $usuario->empresa;
        $tipoCuenta = $this->createTipoCuenta();

        $cuenta = CuentaContable::create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Cuenta Sola',
            'codigo' => '1.SOL.01',
            'tipo_cuenta_id' => $tipoCuenta->id,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ]);

        $data = [
            'fecha_asiento' => now()->format('Y-m-d'),
            'concepto' => 'Asiento con un solo detalle',
            'detalles' => [
                [
                    'cuenta_contable_id' => $cuenta->id,
                    'debe' => 10000,
                    'haber' => 0,
                ],
            ],
        ];

        $response = $this->authenticatedJson('POST', '/api/asientos-contables', $data, $usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function test_no_permite_asientos_sin_autenticacion()
    {
        $response = $this->postJson('/api/asientos-contables', [
            'fecha_asiento' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(401);
    }
}
