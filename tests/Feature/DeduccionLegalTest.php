<?php

namespace Tests\Feature;

use App\Models\DeduccionLegal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeduccionLegalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
    }

    private function crearDeduccion(array $overrides = []): DeduccionLegal
    {
        return DeduccionLegal::create(array_merge([
            'codigo' => 'CCSS-OBR',
            'nombre' => 'CCSS Obrero',
            'descripcion' => 'Cuota obrera CCSS',
            'tipo' => 'ccss_obrero',
            'porcentaje_base' => 5.50,
            'aplica_sobre' => 'salario_bruto',
            'es_obligatoria' => true,
            'activa' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_deducciones(): void
    {
        $this->crearDeduccion();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', '/api/deducciones-legales', [], $usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_deduccion(): void
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('POST', '/api/deducciones-legales', [
            'codigo' => 'CCSS-PAT',
            'nombre' => 'CCSS Patronal',
            'tipo' => 'ccss_patronal',
            'porcentaje_base' => 9.25,
            'aplica_sobre' => 'salario_bruto',
        ], $usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_deduccion(): void
    {
        $deduccion = $this->crearDeduccion();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('GET', "/api/deducciones-legales/{$deduccion->id}", [], $usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_deduccion(): void
    {
        $deduccion = $this->crearDeduccion();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('PUT', "/api/deducciones-legales/{$deduccion->id}", [
            'nombre' => 'CCSS Obrero Actualizado',
            'porcentaje_base' => 5.75,
        ], $usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_deduccion(): void
    {
        $deduccion = $this->crearDeduccion();
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('DELETE', "/api/deducciones-legales/{$deduccion->id}", [], $usuario);

        $response->assertOk();
    }

    #[Test]
    public function requiere_nombre_al_crear(): void
    {
        $usuario = $this->createAdminUsuario();

        $response = $this->authenticatedJson('POST', '/api/deducciones-legales', [
            'codigo' => 'TST',
            'tipo' => 'otros',
        ], $usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/deducciones-legales');

        $response->assertUnauthorized();
    }
}
