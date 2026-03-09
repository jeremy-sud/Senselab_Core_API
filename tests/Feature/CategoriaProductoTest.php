<?php

namespace Tests\Feature;

use App\Models\CategoriaProducto;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoriaProductoTest extends TestCase
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

    private function crearCategoria(array $overrides = []): CategoriaProducto
    {
        return CategoriaProducto::create(array_merge([
            'nombre' => 'Categoría Test ' . uniqid(),
            'descripcion' => 'Descripción de prueba',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_categorias_productos(): void
    {
        $this->crearCategoria(['nombre' => 'Electrónica']);
        $this->crearCategoria(['nombre' => 'Alimentos']);

        $response = $this->authenticatedJson('GET', '/api/categorias-productos', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_categoria_producto(): void
    {
        $data = [
            'nombre' => 'Nueva Categoría',
            'descripcion' => 'Descripción de la categoría',
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/categorias-productos', $data, $this->usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('categorias_productos', ['nombre' => 'Nueva Categoría']);
    }

    #[Test]
    public function puede_ver_categoria_producto(): void
    {
        $categoria = $this->crearCategoria(['nombre' => 'Electrónica']);

        $response = $this->authenticatedJson('GET', "/api/categorias-productos/{$categoria->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_categoria_producto(): void
    {
        $categoria = $this->crearCategoria(['nombre' => 'Original']);

        $response = $this->authenticatedJson('PUT', "/api/categorias-productos/{$categoria->id}", [
            'nombre' => 'Actualizada',
            'descripcion' => 'Descripción actualizada',
        ], $this->usuario);

        $response->assertOk();
        $this->assertDatabaseHas('categorias_productos', ['id' => $categoria->id, 'nombre' => 'Actualizada']);
    }

    #[Test]
    public function puede_eliminar_categoria_producto(): void
    {
        $categoria = $this->crearCategoria();

        $response = $this->authenticatedJson('DELETE', "/api/categorias-productos/{$categoria->id}", [], $this->usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function no_permite_crear_categoria_con_nombre_duplicado(): void
    {
        $this->crearCategoria(['nombre' => 'Electrónica']);

        $response = $this->authenticatedJson('POST', '/api/categorias-productos', [
            'nombre' => 'Electrónica',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_nombre_al_crear_categoria(): void
    {
        $response = $this->authenticatedJson('POST', '/api/categorias-productos', [
            'descripcion' => 'Sin nombre',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion_para_listar_categorias(): void
    {
        $response = $this->getJson('/api/categorias-productos');

        $response->assertUnauthorized();
    }
}
