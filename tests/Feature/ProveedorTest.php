<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProveedorTest extends TestCase
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

    private function datosProveedorValido(array $overrides = []): array
    {
        return array_merge([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => 'juridica',
            'numero_identificacion' => '3101' . rand(100000, 999999),
            'nombre' => 'Proveedor Test S.A.',
            'nombre_comercial' => 'ProvTest',
            'email' => 'proveedor@test.com',
            'telefono' => '+506 2222-4444',
            'direccion' => 'San José, Costa Rica',
            'activo' => true,
        ], $overrides);
    }

    private function crearProveedor(array $overrides = []): Proveedor
    {
        return Proveedor::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '02',
            'numero_identificacion' => '3101' . rand(100000, 999999),
            'nombre' => 'Proveedor Test',
            'email' => 'prov' . rand(100, 999) . '@test.com',
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_proveedores(): void
    {
        $this->crearProveedor(['nombre' => 'Proveedor 1']);
        $this->crearProveedor(['nombre' => 'Proveedor 2']);

        $response = $this->authenticatedJson('GET', '/api/proveedores', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_proveedor_con_datos_validos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/proveedores', $this->datosProveedorValido(), $this->usuario);

        $response->assertStatus(201);

        $this->assertDatabaseHas('proveedores', [
            'nombre' => 'Proveedor Test S.A.',
        ]);
    }

    #[Test]
    public function puede_ver_proveedor(): void
    {
        $proveedor = $this->crearProveedor();

        $response = $this->authenticatedJson('GET', "/api/proveedores/{$proveedor->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_proveedor(): void
    {
        $proveedor = $this->crearProveedor();

        $response = $this->authenticatedJson('PUT', "/api/proveedores/{$proveedor->id}", [
            'nombre' => 'Proveedor Actualizado',
        ], $this->usuario);

        $response->assertOk();

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'nombre' => 'Proveedor Actualizado',
        ]);
    }

    #[Test]
    public function puede_eliminar_proveedor(): void
    {
        $proveedor = $this->crearProveedor();

        $response = $this->authenticatedJson('DELETE', "/api/proveedores/{$proveedor->id}", [], $this->usuario);

        $response->assertOk();

        $this->assertDatabaseHas('proveedores', [
            'id' => $proveedor->id,
            'eliminado' => true,
        ]);
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/proveedores', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['empresa_id', 'tipo_identificacion', 'numero_identificacion', 'nombre']);
    }

    #[Test]
    public function validacion_tipo_identificacion_invalido(): void
    {
        $datos = $this->datosProveedorValido(['tipo_identificacion' => 'invalido']);

        $response = $this->authenticatedJson('POST', '/api/proveedores', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tipo_identificacion']);
    }

    #[Test]
    public function validacion_email_formato_invalido(): void
    {
        $datos = $this->datosProveedorValido(['email' => 'no-es-email']);

        $response = $this->authenticatedJson('POST', '/api/proveedores', $datos, $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    #[Test]
    public function requiere_autenticacion_para_listar(): void
    {
        $response = $this->getJson('/api/proveedores');

        $response->assertUnauthorized();
    }

    #[Test]
    public function requiere_autenticacion_para_crear(): void
    {
        $response = $this->postJson('/api/proveedores', []);

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_buscar_proveedores(): void
    {
        $this->crearProveedor(['nombre' => 'Distribuidora Nacional']);
        $this->crearProveedor(['nombre' => 'Importaciones CR']);

        $response = $this->authenticatedJson('GET', '/api/proveedores?search=Nacional', [], $this->usuario);

        $response->assertOk();
    }
}
