<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\FormaPago;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FormaPagoTest extends TestCase
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

    private int $codigoSeq = 1;

    private function crearFormaPago(array $overrides = []): FormaPago
    {
        return FormaPago::create(array_merge([
            'codigo_dgt' => 'FP' . str_pad((string) $this->codigoSeq++, 3, '0', STR_PAD_LEFT),
            'nombre' => 'Forma Pago ' . uniqid(),
            'descripcion' => 'Forma de pago de prueba',
            'activo' => true,
        ], $overrides));
    }

    #[Test]
    public function puede_listar_formas_pago(): void
    {
        $this->crearFormaPago(['nombre' => 'Efectivo']);
        $this->crearFormaPago(['nombre' => 'Tarjeta']);

        $response = $this->authenticatedJson('GET', '/api/formas-pago', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_forma_pago(): void
    {
        $data = [
            'codigo_dgt' => 'TB01',
            'nombre' => 'Transferencia Bancaria',
            'descripcion' => 'Pago por transferencia',
            'activo' => true,
        ];

        $response = $this->authenticatedJson('POST', '/api/formas-pago', $data, $this->usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('formas_pago', ['nombre' => 'Transferencia Bancaria']);
    }

    #[Test]
    public function puede_ver_forma_pago(): void
    {
        $formaPago = $this->crearFormaPago(['nombre' => 'Tarjeta Débito']);

        $response = $this->authenticatedJson('GET', "/api/formas-pago/{$formaPago->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_forma_pago(): void
    {
        $formaPago = $this->crearFormaPago(['nombre' => 'Original']);

        $response = $this->authenticatedJson('PUT', "/api/formas-pago/{$formaPago->id}", [
            'nombre' => 'Actualizada',
            'descripcion' => 'Descripción actualizada',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_forma_pago(): void
    {
        $formaPago = $this->crearFormaPago();

        $response = $this->authenticatedJson('DELETE', "/api/formas-pago/{$formaPago->id}", [], $this->usuario);

        $response->assertStatus(200);
    }

    #[Test]
    public function requiere_nombre_al_crear_forma_pago(): void
    {
        $response = $this->authenticatedJson('POST', '/api/formas-pago', [
            'descripcion' => 'Sin nombre',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function no_permite_nombre_duplicado(): void
    {
        $this->crearFormaPago(['nombre' => 'Efectivo']);

        $response = $this->authenticatedJson('POST', '/api/formas-pago', [
            'codigo_dgt' => 'DUP1',
            'nombre' => 'Efectivo',
        ], $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function requiere_autenticacion(): void
    {
        $response = $this->getJson('/api/formas-pago');

        $response->assertUnauthorized();
    }
}
