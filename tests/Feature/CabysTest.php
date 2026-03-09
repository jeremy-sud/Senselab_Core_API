<?php

namespace Tests\Feature;

use App\Models\Cabys;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CabysTest extends TestCase
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

    private function crearCaby(array $overrides = []): Cabys
    {
        return Cabys::create(array_merge([
            'codigo' => str_pad((string) rand(1000000000000, 9999999999999), 13, '0', STR_PAD_LEFT),
            'descripcion' => 'Producto CABYS Test',
            'impuesto_iva_predeterminado' => 13.00,
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_cabys(): void
    {
        $this->crearCaby();

        $response = $this->authenticatedJson('GET', '/api/cabys', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_ver_cabys_por_codigo(): void
    {
        $caby = $this->crearCaby(['codigo' => '1111111111111']);

        $response = $this->authenticatedJson('GET', "/api/cabys/{$caby->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_buscar_cabys(): void
    {
        $this->crearCaby(['codigo' => '5555555555555', 'descripcion' => 'Servicio especial búsqueda']);

        $response = $this->authenticatedJson('GET', '/api/cabys/buscar?termino=especial', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function buscar_sin_termino_retorna_error(): void
    {
        $response = $this->authenticatedJson('GET', '/api/cabys/buscar', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['termino']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/cabys');

        $response->assertUnauthorized();
    }

    #[Test]
    public function ver_codigo_inexistente_retorna_404(): void
    {
        $response = $this->authenticatedJson('GET', '/api/cabys/0000000000000', [], $this->usuario);

        $response->assertNotFound();
    }
}
