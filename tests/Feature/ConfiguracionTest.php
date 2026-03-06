<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ConfiguracionTest extends TestCase
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

    private function crearConfiguracion(array $overrides = []): Configuracion
    {
        static $counter = 0;
        $counter++;

        return Configuracion::create(array_merge([
            'empresa_id' => $this->empresa->id,
            'clave' => 'config_test_' . $counter . '_' . uniqid(),
            'valor' => 'valor_test',
            'tipo_dato' => 'string',
            'descripcion' => 'Configuración de prueba',
        ], $overrides));
    }

    #[Test]
    public function puede_listar_configuraciones()
    {
        $this->crearConfiguracion(['clave' => 'conf_list_1']);
        $this->crearConfiguracion(['clave' => 'conf_list_2']);

        $response = $this->authenticatedJson('GET', '/api/configuraciones', [], $this->usuario);

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function puede_crear_configuracion()
    {
        $data = [
            'clave' => 'moneda_principal',
            'valor' => '{"code":"CRC"}',
            'tipo_dato' => 'json',
            'descripcion' => 'Moneda principal del sistema',
        ];

        $response = $this->authenticatedJson('POST', '/api/configuraciones', $data, $this->usuario);

        $response->assertStatus(201);
        $this->assertDatabaseHas('configuraciones', [
            'clave' => 'moneda_principal',
            'empresa_id' => $this->empresa->id,
        ]);
    }

    #[Test]
    public function puede_ver_configuracion_especifica()
    {
        $config = $this->crearConfiguracion(['clave' => 'ver_config']);

        $response = $this->authenticatedJson('GET', "/api/configuraciones/{$config->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_actualizar_configuracion()
    {
        $config = $this->crearConfiguracion([
            'clave' => 'color_tema',
            'valor' => 'azul',
        ]);

        $response = $this->authenticatedJson('PUT', "/api/configuraciones/{$config->id}", [
            'clave' => 'color_tema',
            'valor' => '{"color":"rojo"}',
            'tipo_dato' => 'json',
        ], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_eliminar_configuracion()
    {
        $config = $this->crearConfiguracion(['clave' => 'eliminar_config']);

        $response = $this->authenticatedJson('DELETE', "/api/configuraciones/{$config->id}", [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_buscar_configuracion_por_clave()
    {
        $this->crearConfiguracion([
            'clave' => 'idioma_sistema',
            'valor' => 'es',
        ]);

        $response = $this->authenticatedJson('GET', '/api/configuraciones/clave/idioma_sistema', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function puede_obtener_valor_por_clave()
    {
        $this->crearConfiguracion([
            'clave' => 'zona_horaria',
            'valor' => 'America/Costa_Rica',
        ]);

        $response = $this->authenticatedJson('GET', '/api/configuraciones/valor/zona_horaria', [], $this->usuario);

        $response->assertOk();
    }

    #[Test]
    public function validacion_clave_unica_por_empresa()
    {
        $this->crearConfiguracion(['clave' => 'clave_duplicada']);

        $data = [
            'clave' => 'clave_duplicada',
            'valor' => '{"v":1}',
            'tipo_dato' => 'json',
        ];

        $response = $this->authenticatedJson('POST', '/api/configuraciones', $data, $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function validacion_tipo_dato_invalido()
    {
        $data = [
            'clave' => 'test_tipo',
            'valor' => 'valor',
            'tipo_dato' => 'invalido',
        ];


        $response = $this->authenticatedJson('POST', '/api/configuraciones', $data, $this->usuario);

        $response->assertStatus(422);
    }

    #[Test]
    public function no_puede_acceder_sin_autenticacion()
    {
        $response = $this->getJson('/api/configuraciones');

        $response->assertUnauthorized();
    }
}
