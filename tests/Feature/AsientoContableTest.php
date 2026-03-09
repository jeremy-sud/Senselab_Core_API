<?php

namespace Tests\Feature;

use App\Models\AsientoContable;
use App\Models\CuentaContable;
use App\Models\Empresa;
use App\Models\TipoCuenta;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AsientoContableTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected CuentaContable $cuentaDebe;
    protected CuentaContable $cuentaHaber;

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

        $this->cuentaDebe = CuentaContable::create([
            'empresa_id' => $this->empresa->id,
            'tipo_cuenta_id' => $tipoCuenta->id,
            'codigo' => '1-01-001',
            'nombre' => 'Caja General',
            'naturaleza' => 'Deudora',
            'nivel' => 3,
            'acepta_movimientos' => true,
            'activo' => true,
        ]);

        $this->cuentaHaber = CuentaContable::create([
            'empresa_id' => $this->empresa->id,
            'tipo_cuenta_id' => $tipoCuenta->id,
            'codigo' => '1-01-002',
            'nombre' => 'Bancos',
            'naturaleza' => 'Deudora',
            'nivel' => 3,
            'acepta_movimientos' => true,
            'activo' => true,
        ]);
    }

    private function datosAsientoValido(array $overrides = []): array
    {
        return array_merge([
            'fecha_asiento' => now()->format('Y-m-d'),
            'concepto' => 'Asiento de prueba',
            'estado' => 'Borrador',
            'detalles' => [
                [
                    'cuenta_contable_id' => $this->cuentaDebe->id,
                    'debe' => 5000.00,
                    'haber' => 0.00,
                    'descripcion' => 'Débito a caja',
                ],
                [
                    'cuenta_contable_id' => $this->cuentaHaber->id,
                    'debe' => 0.00,
                    'haber' => 5000.00,
                    'descripcion' => 'Crédito de bancos',
                ],
            ],
        ], $overrides);
    }

    private function crearAsiento(array $overrides = []): AsientoContable
    {
        return AsientoContable::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'numero_asiento' => rand(1, 99999),
            'fecha_asiento' => now()->format('Y-m-d'),
            'concepto' => 'Asiento de prueba',
            'total_debe' => 5000.00,
            'total_haber' => 5000.00,
            'estado' => 'Borrador',
            'usuario_id' => $this->usuario->id,
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_asientos_contables(): void
    {
        $this->crearAsiento(['numero_asiento' => 1]);
        $this->crearAsiento(['numero_asiento' => 2]);

        $response = $this->authenticatedJson('GET', '/api/asientos-contables', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_asiento_contable_con_datos_validos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/asientos-contables', $this->datosAsientoValido(), $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_asiento_contable(): void
    {
        $asiento = $this->crearAsiento();

        $response = $this->authenticatedJson('GET', "/api/asientos-contables/{$asiento->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_asiento_borrador(): void
    {
        $asiento = $this->crearAsiento(['estado' => 'Borrador']);

        $response = $this->authenticatedJson('PUT', "/api/asientos-contables/{$asiento->id}", [
            'concepto' => 'Concepto actualizado',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function no_puede_actualizar_asiento_mayorizado(): void
    {
        $asiento = $this->crearAsiento(['estado' => 'Mayorizado']);

        $response = $this->authenticatedJson('PUT', "/api/asientos-contables/{$asiento->id}", [
            'concepto' => 'Intento de cambio',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function puede_eliminar_asiento_borrador(): void
    {
        $asiento = $this->crearAsiento(['estado' => 'Borrador']);

        $response = $this->authenticatedJson('DELETE', "/api/asientos-contables/{$asiento->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function no_puede_eliminar_asiento_mayorizado(): void
    {
        $asiento = $this->crearAsiento(['estado' => 'Mayorizado']);

        $response = $this->authenticatedJson('DELETE', "/api/asientos-contables/{$asiento->id}", [], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function validacion_debe_igual_haber(): void
    {
        $datos = $this->datosAsientoValido();
        $datos['detalles'][0]['debe'] = 5000.00;
        $datos['detalles'][1]['haber'] = 3000.00;

        $response = $this->authenticatedJson('POST', '/api/asientos-contables', $datos, $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function validacion_minimo_dos_detalles(): void
    {
        $datos = $this->datosAsientoValido();
        $datos['detalles'] = [
            [
                'cuenta_contable_id' => $this->cuentaDebe->id,
                'debe' => 1000.00,
                'haber' => 1000.00,
            ],
        ];

        $response = $this->authenticatedJson('POST', '/api/asientos-contables', $datos, $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/asientos-contables', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_asiento', 'concepto', 'detalles']);
    }

    #[Test]
    public function requiere_autenticacion_para_listar(): void
    {
        $response = $this->getJson('/api/asientos-contables');

        $response->assertUnauthorized();
    }

    #[Test]
    public function requiere_autenticacion_para_crear(): void
    {
        $response = $this->postJson('/api/asientos-contables', []);

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_filtrar_por_estado(): void
    {
        $this->crearAsiento(['numero_asiento' => 10, 'estado' => 'Borrador']);
        $this->crearAsiento(['numero_asiento' => 11, 'estado' => 'Mayorizado']);

        $response = $this->authenticatedJson('GET', '/api/asientos-contables?estado=Borrador', [], $this->usuario);

        $response->assertOk();
    }
}
