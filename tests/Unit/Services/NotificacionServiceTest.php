<?php

namespace Tests\Unit\Services;

use App\Models\Empresa;
use App\Models\Notificacion;
use App\Models\Usuario;
use App\Services\NotificacionService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificacionServiceTest extends TestCase
{
    protected NotificacionService $service;
    protected Empresa $empresa;
    protected Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificacionService();
        $this->empresa = $this->createEmpresa();
        $this->usuario = $this->createUsuario(['empresa_id' => $this->empresa->id]);
    }

    private function crearNotificacion(array $override = []): Notificacion
    {
        return Notificacion::create(array_merge([
            'usuario_id' => $this->usuario->id,
            'empresa_id' => $this->empresa->id,
            'tipo' => 'info',
            'titulo' => 'Notificación ' . uniqid(),
            'mensaje' => 'Mensaje de prueba',
            'prioridad' => Notificacion::PRIORIDAD_NORMAL,
            'leida' => false,
        ], $override));
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginado(): void
    {
        $this->crearNotificacion();
        $this->crearNotificacion();

        $resultado = $this->service->listar(['usuario_id' => $this->usuario->id]);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_filtra_por_tipo(): void
    {
        $this->crearNotificacion(['tipo' => 'info']);
        $this->crearNotificacion(['tipo' => 'error']);

        $resultado = $this->service->listar([
            'usuario_id' => $this->usuario->id,
            'tipo' => 'info',
        ]);

        foreach ($resultado as $notif) {
            $this->assertEquals('info', $notif->tipo);
        }
    }

    #[Test]
    public function listar_filtra_por_leida(): void
    {
        $this->crearNotificacion(['leida' => false]);
        $this->crearNotificacion(['leida' => true]);

        $resultado = $this->service->listar([
            'usuario_id' => $this->usuario->id,
            'leida' => false,
        ]);

        foreach ($resultado as $notif) {
            $this->assertFalse((bool) $notif->leida);
        }
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_notificacion_exitosamente(): void
    {
        $data = [
            'usuario_id' => $this->usuario->id,
            'empresa_id' => $this->empresa->id,
            'tipo' => 'warning',
            'titulo' => 'Test',
            'mensaje' => 'Mensaje test',
        ];

        $notificacion = $this->service->crear($data);

        $this->assertInstanceOf(Notificacion::class, $notificacion);
        $this->assertFalse((bool) $notificacion->leida);
        $this->assertDatabaseHas('notificaciones', ['id' => $notificacion->id]);
    }

    // ─── marcarLeida() ──────────────────────────────────────────

    #[Test]
    public function marcar_leida_exitosamente(): void
    {
        $notificacion = $this->crearNotificacion(['leida' => false]);

        $resultado = $this->service->marcarLeida($notificacion);

        $this->assertTrue((bool) $resultado->leida);
    }

    // ─── marcarTodasLeidas() ────────────────────────────────────

    #[Test]
    public function marcar_todas_leidas_exitosamente(): void
    {
        $this->crearNotificacion(['leida' => false]);
        $this->crearNotificacion(['leida' => false]);

        $updated = $this->service->marcarTodasLeidas($this->usuario->id);

        $this->assertEquals(2, $updated);
    }

    // ─── contarNoLeidas() ───────────────────────────────────────

    #[Test]
    public function contar_no_leidas(): void
    {
        $this->crearNotificacion(['leida' => false]);
        $this->crearNotificacion(['leida' => false]);
        $this->crearNotificacion(['leida' => true]);

        $count = $this->service->contarNoLeidas($this->usuario->id);

        $this->assertEquals(2, $count);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_notificacion_exitosamente(): void
    {
        $notificacion = $this->crearNotificacion();
        $id = $notificacion->id;

        $resultado = $this->service->eliminar($notificacion);

        $this->assertTrue($resultado);
        $this->assertDatabaseMissing('notificaciones', ['id' => $id]);
    }
}
