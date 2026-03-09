<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\SalidaInventario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SalidaInventarioTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Almacen $almacen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();

        $this->almacen = Almacen::create([
            'empresa_id' => $this->usuario->empresa_id,
            'nombre' => 'Almacén Principal',
            'codigo' => 'ALM-001',
            'activo' => true,
        ]);
    }

    private function crearSalida(array $overrides = []): SalidaInventario
    {
        return SalidaInventario::create(array_merge([
            'empresa_id' => $this->usuario->empresa_id,
            'almacen_id' => $this->almacen->id,
            'fecha_salida' => now()->format('Y-m-d H:i:s'),
            'tipo_salida' => 'Venta',
            'estado' => 'Pendiente',
            'monto_total' => 50000.00,
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_salidas(): void
    {
        $this->crearSalida();

        $response = $this->authenticatedJson('GET', '/api/salidas-inventario', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /**
     * Crear salida requiere detalles[] con productos — se prueba solo validación.
     */
    #[Test]
    public function valida_datos_al_crear(): void
    {
        $response = $this->authenticatedJson('POST', '/api/salidas-inventario', [
            'almacen_id' => $this->almacen->id,
            'fecha_salida' => now()->format('Y-m-d H:i:s'),
            'tipo_salida' => 'Ajuste Negativo',
            'observaciones' => 'Test salida',
        ], $this->usuario);

        // Without detalles returns 422
        $response->assertStatus(422);
    }

    #[Test]
    public function puede_ver_salida(): void
    {
        $salida = $this->crearSalida();

        $response = $this->authenticatedJson('GET', "/api/salidas-inventario/{$salida->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_salida(): void
    {
        $salida = $this->crearSalida();

        $response = $this->authenticatedJson('PUT', "/api/salidas-inventario/{$salida->id}", [
            'descripcion' => 'Salida actualizada',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_salida(): void
    {
        $salida = $this->crearSalida();

        $response = $this->authenticatedJson('DELETE', "/api/salidas-inventario/{$salida->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_almacen_al_crear(): void
    {
        $response = $this->authenticatedJson('POST', '/api/salidas-inventario', [
            'tipo_salida' => 'Venta',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/salidas-inventario');

        $response->assertUnauthorized();
    }
}
