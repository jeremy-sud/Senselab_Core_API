<?php

namespace Tests\Unit\Services;

use App\Models\MensajeHacienda;
use App\Services\MensajeHaciendaService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MensajeHaciendaServiceTest extends TestCase
{
    protected MensajeHaciendaService $service;
    private \App\Models\Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MensajeHaciendaService();
        $this->empresa = $this->createEmpresa();
    }

    private function crearMensaje(array $override = []): MensajeHacienda
    {
        $mensaje = new MensajeHacienda(array_merge([
            'clave_numerica' => uniqid(),
            'tipo_mensaje' => 'aceptacion',
            'fecha_emision' => now(),
            'estado' => 'pendiente',
            'intentos_envio' => 0,
        ], $override));
        $mensaje->empresa_id = $this->empresa->id;
        $mensaje->saveQuietly();

        return $mensaje->fresh();
    }

    // ─── listar() ───────────────────────────────────────────────

    #[Test]
    public function listar_retorna_paginacion(): void
    {
        $this->crearMensaje();
        $this->crearMensaje();

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id], 10);

        $this->assertGreaterThanOrEqual(2, $resultado->total());
    }

    #[Test]
    public function listar_filtra_por_empresa_id(): void
    {
        $this->crearMensaje();

        $otraEmpresa = $this->createEmpresa(['email' => uniqid() . '@empresa.com']);
        $otroMensaje = new MensajeHacienda([
            'clave_numerica' => uniqid(),
            'tipo_mensaje' => 'aceptacion',
            'fecha_emision' => now(),
            'estado' => 'pendiente',
            'intentos_envio' => 0,
        ]);
        $otroMensaje->empresa_id = $otraEmpresa->id;
        $otroMensaje->saveQuietly();

        $resultado = $this->service->listar(['empresa_id' => $this->empresa->id]);

        foreach ($resultado->items() as $item) {
            $this->assertEquals($this->empresa->id, $item->empresa_id);
        }
    }

    #[Test]
    public function listar_filtra_por_estado(): void
    {
        $this->crearMensaje(['estado' => 'pendiente']);
        $this->crearMensaje(['estado' => 'procesado']);

        $resultado = $this->service->listar([
            'empresa_id' => $this->empresa->id,
            'estado' => 'pendiente',
        ]);

        foreach ($resultado->items() as $item) {
            $this->assertEquals('pendiente', $item->estado);
        }
    }

    #[Test]
    public function listar_filtra_por_tipo_mensaje(): void
    {
        $this->crearMensaje(['tipo_mensaje' => 'aceptacion']);
        $this->crearMensaje(['tipo_mensaje' => 'rechazo']);

        $resultado = $this->service->listar([
            'empresa_id' => $this->empresa->id,
            'tipo_mensaje' => 'aceptacion',
        ]);

        foreach ($resultado->items() as $item) {
            $this->assertEquals('aceptacion', $item->tipo_mensaje);
        }
    }

    #[Test]
    public function listar_filtra_por_rango_fechas(): void
    {
        $this->crearMensaje(['fecha_emision' => '2025-01-15 10:00:00']);
        $this->crearMensaje(['fecha_emision' => '2025-06-15 10:00:00']);

        $resultado = $this->service->listar([
            'empresa_id' => $this->empresa->id,
            'fecha_desde' => '2025-01-01',
            'fecha_hasta' => '2025-02-01',
        ]);

        $this->assertGreaterThanOrEqual(1, $resultado->total());
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_mensaje_basico(): void
    {
        $mensaje = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'clave_numerica' => 'CLAVE123',
            'tipo_mensaje' => 'aceptacion',
            'fecha_emision' => now()->toDateTimeString(),
            'estado' => 'pendiente',
        ]);

        $this->assertDatabaseHas('mensajes_hacienda', [
            'id' => $mensaje->id,
            'clave_numerica' => 'CLAVE123',
        ]);
    }

    #[Test]
    public function crear_asigna_fecha_procesamiento_si_procesado(): void
    {
        $mensaje = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'clave_numerica' => 'CLAVE_PROC',
            'tipo_mensaje' => 'aceptacion',
            'fecha_emision' => now()->toDateTimeString(),
            'estado' => 'procesado',
        ]);

        $this->assertNotNull($mensaje->fresh()->fecha_procesamiento);
    }

    #[Test]
    public function crear_asigna_intentos_envio_cero(): void
    {
        $mensaje = $this->service->crear([
            'empresa_id' => $this->empresa->id,
            'clave_numerica' => 'CLAVE_INT',
            'tipo_mensaje' => 'aceptacion',
            'fecha_emision' => now()->toDateTimeString(),
            'estado' => 'pendiente',
        ]);

        $this->assertEquals(0, $mensaje->fresh()->intentos_envio);
    }

    // ─── actualizar() ──────────────────────────────────────────

    #[Test]
    public function actualizar_asigna_fecha_procesamiento_si_cambia_a_procesado(): void
    {
        $mensaje = $this->crearMensaje(['estado' => 'pendiente']);

        $this->service->actualizar($mensaje, ['estado' => 'procesado']);

        $this->assertNotNull($mensaje->fresh()->fecha_procesamiento);
    }

    #[Test]
    public function actualizar_mensaje(): void
    {
        $mensaje = $this->crearMensaje();

        $this->service->actualizar($mensaje, ['detalle_mensaje' => 'Actualizado']);

        $this->assertEquals('Actualizado', $mensaje->fresh()->detalle_mensaje);
    }

    // ─── eliminar() ─────────────────────────────────────────────

    #[Test]
    public function eliminar_soft_delete(): void
    {
        $mensaje = $this->crearMensaje();

        $result = $this->service->eliminar($mensaje);

        $this->assertTrue($result);
        $deleted = MensajeHacienda::withoutGlobalScope('notDeleted')->find($mensaje->id);
        $this->assertNotNull($deleted);
        $this->assertTrue((bool) $deleted->eliminado);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_carga_relaciones(): void
    {
        $mensaje = $this->crearMensaje();

        $resultado = $this->service->obtener($mensaje->id);

        $this->assertEquals($mensaje->id, $resultado->id);
    }
}
