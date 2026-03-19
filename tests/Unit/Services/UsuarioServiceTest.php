<?php

namespace Tests\Unit\Services;

use App\Exceptions\BusinessException;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Services\UsuarioService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UsuarioServiceTest extends TestCase
{
    protected UsuarioService $service;
    protected Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UsuarioService();
        $this->empresa = $this->createEmpresa();
    }

    // ─── listarTodos() ──────────────────────────────────────────

    #[Test]
    public function listar_todos_retorna_coleccion(): void
    {
        $this->createUsuario(['empresa_id' => $this->empresa->id]);
        $this->createUsuario(['empresa_id' => $this->empresa->id, 'email' => 'otro' . uniqid() . '@test.com']);

        $resultado = $this->service->listarTodos(['empresa_id' => $this->empresa->id]);

        $this->assertGreaterThanOrEqual(2, $resultado->count());
    }

    #[Test]
    public function listar_todos_filtra_por_activo(): void
    {
        $this->createUsuario(['empresa_id' => $this->empresa->id, 'activo' => true]);
        $this->createUsuario(['empresa_id' => $this->empresa->id, 'activo' => false, 'email' => 'inactivo' . uniqid() . '@test.com']);

        $resultado = $this->service->listarTodos([
            'empresa_id' => $this->empresa->id,
            'activo' => true,
        ]);

        foreach ($resultado as $usuario) {
            $this->assertTrue((bool) $usuario->activo);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_usuario_exitosamente(): void
    {
        $data = [
            'nombre' => 'Juan',
            'apellidos' => 'Pérez',
            'email' => 'juan' . uniqid() . '@test.com',
            'password' => 'Password123!',
            'empresa_id' => $this->empresa->id,
            'activo' => true,
        ];

        $usuario = $this->service->crear($data);

        $this->assertInstanceOf(Usuario::class, $usuario);
        $this->assertDatabaseHas('usuarios', ['id' => $usuario->id, 'email' => $data['email']]);
    }

    #[Test]
    public function crear_usuario_hashea_password(): void
    {
        $data = [
            'nombre' => 'Test',
            'apellidos' => 'Hash',
            'email' => 'hash' . uniqid() . '@test.com',
            'password' => 'MiPassword123',
            'empresa_id' => $this->empresa->id,
        ];

        $usuario = $this->service->crear($data);

        $this->assertNotEquals('MiPassword123', $usuario->password_hash);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('MiPassword123', $usuario->password_hash));
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_usuario_existente(): void
    {
        $usuario = $this->createUsuario(['empresa_id' => $this->empresa->id]);

        $resultado = $this->service->obtener($usuario->id);

        $this->assertEquals($usuario->id, $resultado->id);
    }

    #[Test]
    public function obtener_usuario_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_usuario_exitosamente(): void
    {
        $usuario = $this->createUsuario(['empresa_id' => $this->empresa->id]);

        $resultado = $this->service->actualizar($usuario, ['nombre' => 'Actualizado']);

        $this->assertEquals('Actualizado', $resultado->nombre);
    }

    #[Test]
    public function actualizar_usuario_hashea_nueva_password(): void
    {
        $usuario = $this->createUsuario(['empresa_id' => $this->empresa->id]);

        $resultado = $this->service->actualizar($usuario, ['password' => 'NuevaPassword456']);

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NuevaPassword456', $resultado->password_hash));
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_usuario_soft_delete(): void
    {
        $usuario = $this->createUsuario(['empresa_id' => $this->empresa->id]);

        $resultado = $this->service->eliminar($usuario);

        $this->assertTrue($resultado);
        $usuario->refresh();
        $this->assertNotNull($usuario->eliminado);
        $this->assertEquals(0, $usuario->activo);
    }

    // ─── cambiarPassword() ──────────────────────────────────────

    #[Test]
    public function cambiar_password_exitosamente(): void
    {
        $usuario = $this->createUsuario([
            'empresa_id' => $this->empresa->id,
            'password_hash' => bcrypt('OldPassword123'),
        ]);

        $this->service->cambiarPassword($usuario, 'OldPassword123', 'NewPassword456');

        $usuario->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPassword456', $usuario->password_hash));
    }

    #[Test]
    public function cambiar_password_con_password_incorrecta_lanza_excepcion(): void
    {
        $usuario = $this->createUsuario([
            'empresa_id' => $this->empresa->id,
            'password_hash' => bcrypt('CorrectPassword'),
        ]);

        $this->expectException(BusinessException::class);

        $this->service->cambiarPassword($usuario, 'WrongPassword', 'NewPassword456');
    }
}
