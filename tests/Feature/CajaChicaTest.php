<?php

namespace Tests\Feature;

use App\Models\CajaChica;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CajaChicaTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected Empleado $empleado;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;

        $this->empleado = Empleado::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Juan',
            'primer_apellido' => 'Pérez',
            'tipo_documento' => 'cedula_fisica',
            'numero_documento' => '123456789',
            'email' => 'juan@test.com',
            'fecha_ingreso' => now()->format('Y-m-d'),
            'activo' => true,
        ]);
    }

    private function crearCajaChica(array $overrides = []): CajaChica
    {
        return CajaChica::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Caja Chica Test',
            'monto_inicial' => 50000,
            'saldo_actual' => 50000,
            'responsable_id' => $this->empleado->id,
            'fecha_apertura' => now()->format('Y-m-d'),
            'estado' => 'Abierta',
        ], $overrides));
    }

    #[Test]
    public function puede_listar_cajas_chicas()
    {
        $this->crearCajaChica(['nombre' => 'Caja 1']);
        $this->crearCajaChica(['nombre' => 'Caja 2']);

        $response = $this->authenticatedJson('GET', '/api/caja-chica', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_caja_chica()
    {
        $data = [
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Caja Chica Oficina Principal',
            'monto_inicial' => 50000,
            'responsable_id' => $this->empleado->id,
            'fecha_apertura' => now()->format('Y-m-d'),
            'observaciones' => 'Fondo para gastos menores',
        ];

        $response = $this->authenticatedJson('POST', '/api/caja-chica', $data, $this->usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('caja_chica', [
            'nombre' => 'Caja Chica Oficina Principal',
            'empresa_id' => $this->empresa->id,
        ]);
    }

    #[Test]
    public function puede_ver_caja_chica_especifica()
    {
        $cajaChica = $this->crearCajaChica();

        $response = $this->authenticatedJson('GET', "/api/caja-chica/{$cajaChica->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_caja_chica()
    {
        $cajaChica = $this->crearCajaChica();

        $response = $this->authenticatedJson('PUT', "/api/caja-chica/{$cajaChica->id}", [
            'nombre' => 'Caja Chica Actualizada',
            'monto_inicial' => 75000,
            'responsable_id' => $this->empleado->id,
            'fecha_apertura' => now()->format('Y-m-d'),
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_cerrar_caja_chica()
    {
        $cajaChica = $this->crearCajaChica(['estado' => 'Abierta']);

        $response = $this->authenticatedJson('POST', "/api/caja-chica/{$cajaChica->id}/cerrar", [], $this->usuario);

        $response->assertOk();
        $cajaChica->refresh();
        $this->assertEquals('Cerrada', $cajaChica->estado);
    }

    #[Test]
    public function puede_eliminar_caja_chica()
    {
        $cajaChica = $this->crearCajaChica();

        $response = $this->authenticatedJson('DELETE', "/api/caja-chica/{$cajaChica->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos_al_crear()
    {
        $response = $this->authenticatedJson('POST', '/api/caja-chica', [], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function no_puede_acceder_sin_autenticacion()
    {
        $response = $this->getJson('/api/caja-chica');

        $response->assertUnauthorized();
    }
}
