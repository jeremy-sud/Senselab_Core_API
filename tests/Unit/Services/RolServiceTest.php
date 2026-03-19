<?php

namespace Tests\Unit\Services;

use App\Exceptions\BusinessException;
use App\Models\Permiso;
use App\Models\Rol;
use App\Services\RolService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RolServiceTest extends TestCase
{
    protected RolService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RolService();
    }

    private function crearRol(array $override = []): Rol
    {
        return Rol::create(array_merge([
            'nombre' => 'Rol ' . uniqid(),
            'descripcion' => 'Descripción test',
            'activo' => true,
        ], $override));
    }

    private function crearPermiso(array $override = []): Permiso
    {
        $uid = uniqid();
        return Permiso::create(array_merge([
            'nombre' => 'Permiso ' . $uid,
            'slug' => 'permiso-' . $uid,
            'descripcion' => 'Descripción',
            'modulo' => 'usuarios',
            'activo' => true,
        ], $override));
    }

    // ─── listarTodos() ──────────────────────────────────────────

    #[Test]
    public function listar_todos_retorna_coleccion(): void
    {
        $this->crearRol();
        $this->crearRol();

        $resultado = $this->service->listarTodos();

        $this->assertGreaterThanOrEqual(2, $resultado->count());
    }

    #[Test]
    public function listar_todos_filtra_por_activo(): void
    {
        $this->crearRol(['activo' => true]);
        $this->crearRol(['activo' => false]);

        $resultado = $this->service->listarTodos(['activo' => true]);

        foreach ($resultado as $rol) {
            $this->assertTrue((bool) $rol->activo);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_rol_exitosamente(): void
    {
        $data = [
            'nombre' => 'Gerente',
            'descripcion' => 'Rol de gerente',
            'activo' => true,
        ];

        $rol = $this->service->crear($data);

        $this->assertInstanceOf(Rol::class, $rol);
        $this->assertDatabaseHas('roles', ['id' => $rol->id]);
    }

    #[Test]
    public function crear_rol_con_permisos(): void
    {
        $permiso = $this->crearPermiso();

        $data = [
            'nombre' => 'Rol con permisos',
            'permisos' => [$permiso->id],
        ];

        $rol = $this->service->crear($data);

        $this->assertTrue($rol->permisos->contains('id', $permiso->id));
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_rol_existente(): void
    {
        $rol = $this->crearRol();

        $resultado = $this->service->obtener($rol->id);

        $this->assertEquals($rol->id, $resultado->id);
    }

    #[Test]
    public function obtener_rol_inexistente_lanza_excepcion(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->service->obtener(99999);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_rol_exitosamente(): void
    {
        $rol = $this->crearRol();

        $resultado = $this->service->actualizar($rol, ['descripcion' => 'Actualizada']);

        $this->assertEquals('Actualizada', $resultado->descripcion);
    }

    #[Test]
    public function actualizar_rol_con_permisos(): void
    {
        $rol = $this->crearRol();
        $permiso = $this->crearPermiso();

        $resultado = $this->service->actualizar($rol, ['permisos' => [$permiso->id]]);

        $this->assertTrue($resultado->permisos->contains('id', $permiso->id));
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_rol_sin_usuarios(): void
    {
        $rol = $this->crearRol();

        $resultado = $this->service->eliminar($rol);

        $this->assertTrue($resultado);
    }

    #[Test]
    public function eliminar_rol_con_usuarios_lanza_excepcion(): void
    {
        $rol = $this->crearRol();
        $usuario = $this->createUsuario();
        $usuario->roles()->attach($rol->id);

        $this->expectException(BusinessException::class);

        $this->service->eliminar($rol);
    }

    // ─── asignarPermisos() ──────────────────────────────────────

    #[Test]
    public function asignar_permisos_exitosamente(): void
    {
        $rol = $this->crearRol();
        $permiso1 = $this->crearPermiso();
        $permiso2 = $this->crearPermiso();

        $resultado = $this->service->asignarPermisos($rol, [$permiso1->id, $permiso2->id]);

        $this->assertCount(2, $resultado->permisos);
    }

    // ─── removerPermiso() ───────────────────────────────────────

    #[Test]
    public function remover_permiso_exitosamente(): void
    {
        $rol = $this->crearRol();
        $permiso = $this->crearPermiso();
        $rol->permisos()->attach($permiso->id);

        $resultado = $this->service->removerPermiso($rol, $permiso->id);

        $this->assertFalse($resultado->permisos->contains('id', $permiso->id));
    }
}
