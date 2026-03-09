<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Ruta;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RutaTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;
    }

    private function datosRutaValida(array $overrides = []): array
    {
        return array_merge([
            'nombre' => 'Ruta San José - Cartago',
            'origen' => 'San José',
            'destino' => 'Cartago',
            'distancia_km' => 25.5,
            'duracion_estimada' => 45,
            'tarifa_base' => 650.00,
            'observaciones' => 'Ruta principal',
        ], $overrides);
    }

    private function crearRuta(array $overrides = []): Ruta
    {
        $ruta = new Ruta(array_merge([
            'nombre' => 'Ruta Test ' . rand(1000, 9999),
            'origen' => 'San José',
            'destino' => 'Heredia',
            'distancia_km' => 12.0,
            'duracion_estimada' => 30,
            'tarifa_base' => 500.00,
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
        $ruta->empresa_id = $this->empresa->id;
        $ruta->save();
        return $ruta->fresh();
    }

    #[Test]
    public function puede_listar_rutas(): void
    {
        $this->crearRuta();

        $response = $this->authenticatedJson('GET', '/api/rutas', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_ruta(): void
    {
        $response = $this->authenticatedJson('POST', '/api/rutas', $this->datosRutaValida(), $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_ruta(): void
    {
        $ruta = $this->crearRuta();

        $response = $this->authenticatedJson('GET', "/api/rutas/{$ruta->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_ruta(): void
    {
        $ruta = $this->crearRuta();

        $response = $this->authenticatedJson('PUT', "/api/rutas/{$ruta->id}", [
            'nombre' => 'Ruta Modificada',
            'origen' => 'Alajuela',
            'destino' => 'Limón',
            'tarifa_base' => 800.00,
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_ruta(): void
    {
        $ruta = $this->crearRuta();

        $response = $this->authenticatedJson('DELETE', "/api/rutas/{$ruta->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/rutas', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nombre', 'origen', 'destino', 'tarifa_base']);
    }

    #[Test]
    public function validacion_distancia_negativa(): void
    {
        $datos = $this->datosRutaValida(['distancia_km' => -5]);

        $response = $this->authenticatedJson('POST', '/api/rutas', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['distancia_km']);
    }

    #[Test]
    public function validacion_tarifa_negativa(): void
    {
        $datos = $this->datosRutaValida(['tarifa_base' => -100]);

        $response = $this->authenticatedJson('POST', '/api/rutas', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tarifa_base']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/rutas');

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_listar_rutas_activas(): void
    {
        $this->crearRuta(['activo' => true]);
        $this->crearRuta(['activo' => false]);

        $response = $this->authenticatedJson('GET', '/api/rutas/activas/list', [], $this->usuario);

        $response->assertOk();
    }
}
