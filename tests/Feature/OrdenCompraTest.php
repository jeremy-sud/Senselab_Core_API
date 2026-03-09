<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OrdenCompraTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $usuario;
    protected Empresa $empresa;
    protected Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->seedPermisos();
        $this->usuario = $this->createAdminUsuario();
        $this->empresa = $this->usuario->empresa;

        $this->proveedor = Proveedor::create([
            'empresa_id' => $this->empresa->id,
            'tipo_identificacion' => '02',
            'numero_identificacion' => '3101777666',
            'nombre' => 'Proveedor OC Test',
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    private function datosOrdenValida(array $overrides = []): array
    {
        $producto = $this->createProducto([], $this->empresa);

        return array_merge([
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'usuario_id' => $this->usuario->id,
            'fecha_orden' => '2026-01-15',
            'estado' => 'borrador',
            'detalles' => [
                [
                    'producto_id' => $producto->id,
                    'cantidad' => 10,
                    'precio_unitario' => 5000.00,
                ],
            ],
        ], $overrides);
    }

    private function crearOrden(array $overrides = []): OrdenCompra
    {
        return OrdenCompra::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'usuario_id' => $this->usuario->id,
            'numero_orden' => 'OC-' . rand(1000, 9999),
            'fecha_orden' => '2026-01-15',
            'moneda' => 'CRC',
            'subtotal' => 50000.00,
            'impuesto_total' => 6500.00,
            'total_orden' => 56500.00,
            'estado' => 'borrador',
            'activo' => true,
            'eliminado' => false,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_ordenes_compra(): void
    {
        $this->crearOrden();

        $response = $this->authenticatedJson('GET', '/api/ordenes-compra', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_orden_compra(): void
    {
        $response = $this->authenticatedJson('POST', '/api/ordenes-compra', $this->datosOrdenValida(), $this->usuario);

        $response->assertStatus(201);
    }

    #[Test]
    public function puede_ver_orden_compra(): void
    {
        $orden = $this->crearOrden();

        $response = $this->authenticatedJson('GET', "/api/ordenes-compra/{$orden->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_orden_borrador(): void
    {
        $orden = $this->crearOrden(['estado' => 'borrador']);

        $response = $this->authenticatedJson('PUT', "/api/ordenes-compra/{$orden->id}", [
            'observaciones' => 'Nota actualizada',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_orden_compra(): void
    {
        $orden = $this->crearOrden();

        $response = $this->authenticatedJson('DELETE', "/api/ordenes-compra/{$orden->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_campos_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/ordenes-compra', [], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['proveedor_id', 'fecha_orden', 'estado', 'detalles']);
    }

    #[Test]
    public function validacion_estado_invalido(): void
    {
        $producto = $this->createProducto([], $this->empresa);

        $response = $this->authenticatedJson('POST', '/api/ordenes-compra', [
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'usuario_id' => $this->usuario->id,
            'fecha_orden' => '2026-01-15',
            'estado' => 'invalido',
            'detalles' => [['producto_id' => $producto->id, 'cantidad' => 1, 'precio_unitario' => 100]],
        ], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['estado']);
    }

    #[Test]
    public function validacion_detalles_requeridos(): void
    {
        $response = $this->authenticatedJson('POST', '/api/ordenes-compra', [
            'empresa_id' => $this->empresa->id,
            'proveedor_id' => $this->proveedor->id,
            'usuario_id' => $this->usuario->id,
            'fecha_orden' => '2026-01-15',
            'estado' => 'borrador',
            'detalles' => [],
        ], $this->usuario);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['detalles']);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/ordenes-compra');

        $response->assertUnauthorized();
    }

    #[Test]
    public function puede_filtrar_por_estado(): void
    {
        $this->crearOrden(['estado' => 'borrador']);

        $response = $this->authenticatedJson('GET', '/api/ordenes-compra?estado=borrador', [], $this->usuario);

        $response->assertOk();
    }
}
