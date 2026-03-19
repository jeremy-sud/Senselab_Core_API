<?php

namespace Tests\Unit\Services;

use App\Exceptions\BusinessException;
use App\Models\BusUnidad;
use App\Models\Empresa;
use App\Models\HorarioRuta;
use App\Models\ModeloBus;
use App\Models\Ruta;
use App\Services\HorarioRutaService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HorarioRutaServiceTest extends TestCase
{
    protected HorarioRutaService $service;
    protected Empresa $empresa;
    protected Ruta $ruta;
    protected BusUnidad $bus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new HorarioRutaService();
        $this->empresa = $this->createEmpresa();

        $this->ruta = Ruta::create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Ruta Test',
            'origen' => 'San José',
            'destino' => 'Limón',
            'tarifa_base' => 5000,
            'activo' => true,
            'eliminado' => false,
        ]);

        $modelo = ModeloBus::create(['nombre' => 'Modelo Test']);

        $this->bus = BusUnidad::create([
            'empresa_id' => $this->empresa->id,
            'placa' => 'TST-' . uniqid(),
            'modelo_id' => $modelo->id,
            'capacidad_asientos' => 40,
            'activo' => true,
            'eliminado' => false,
        ]);
    }

    private function crearHorario(array $override = []): HorarioRuta
    {
        return HorarioRuta::create(array_merge([
            'ruta_id' => $this->ruta->id,
            'bus_id' => $this->bus->id,
            'fecha_salida' => now()->addDay()->toDateString(),
            'hora_salida' => '08:00',
            'fecha_llegada_estimada' => now()->addDay()->toDateString(),
            'hora_llegada_estimada' => '12:00',
            'asientos_disponibles' => 40,
            'estado' => 'Programado',
            'activo' => true,
            'eliminado' => false,
        ], $override));
    }

    // ─── crear() ────────────────────────────────────────────────

    #[Test]
    public function crear_horario_exitosamente(): void
    {
        $data = [
            'ruta_id' => $this->ruta->id,
            'bus_id' => $this->bus->id,
            'fecha_salida' => now()->addDays(2)->toDateString(),
            'hora_salida' => '10:00',
            'asientos_disponibles' => 40,
            'estado' => 'Programado',
            'activo' => true,
        ];

        $horario = $this->service->crear($data);

        $this->assertInstanceOf(HorarioRuta::class, $horario);
        $this->assertDatabaseHas('horarios_ruta', ['id' => $horario->id]);
    }

    // ─── obtener() ──────────────────────────────────────────────

    #[Test]
    public function obtener_horario_existente(): void
    {
        $horario = $this->crearHorario();

        $resultado = $this->service->obtener($horario->id);

        $this->assertEquals($horario->id, $resultado->id);
    }

    // ─── actualizar() ───────────────────────────────────────────

    #[Test]
    public function actualizar_horario_programado(): void
    {
        $horario = $this->crearHorario(['estado' => 'Programado']);

        $resultado = $this->service->actualizar($horario, ['hora_salida' => '09:00']);

        $this->assertEquals('09:00', $resultado->hora_salida);
    }

    #[Test]
    public function actualizar_horario_en_viaje_lanza_excepcion(): void
    {
        $horario = $this->crearHorario(['estado' => 'En Viaje']);

        $this->expectException(BusinessException::class);

        $this->service->actualizar($horario, ['hora_salida' => '09:00']);
    }

    // ─── iniciarViaje() ─────────────────────────────────────────

    #[Test]
    public function iniciar_viaje_desde_programado(): void
    {
        $horario = $this->crearHorario(['estado' => 'Programado']);

        $resultado = $this->service->iniciarViaje($horario);

        $this->assertEquals('En Viaje', $resultado->estado);
    }

    #[Test]
    public function iniciar_viaje_desde_estado_invalido_lanza_excepcion(): void
    {
        $horario = $this->crearHorario(['estado' => 'Finalizado']);

        $this->expectException(BusinessException::class);

        $this->service->iniciarViaje($horario);
    }

    // ─── finalizarViaje() ───────────────────────────────────────

    #[Test]
    public function finalizar_viaje_desde_en_viaje(): void
    {
        $horario = $this->crearHorario(['estado' => 'En Viaje']);

        $resultado = $this->service->finalizarViaje($horario);

        $this->assertEquals('Finalizado', $resultado->estado);
    }

    // ─── cancelar() ─────────────────────────────────────────────

    #[Test]
    public function cancelar_viaje_programado(): void
    {
        $horario = $this->crearHorario(['estado' => 'Programado']);

        $resultado = $this->service->cancelar($horario);

        $this->assertEquals('Cancelado', $resultado->estado);
    }

    // ─── asientosDisponibles() ──────────────────────────────────

    #[Test]
    public function asientos_disponibles_retorna_datos(): void
    {
        $horario = $this->crearHorario();

        $resultado = $this->service->asientosDisponibles($horario);

        $this->assertArrayHasKey('asientos_disponibles', $resultado);
        $this->assertArrayHasKey('capacidad_total', $resultado);
        $this->assertArrayHasKey('porcentaje_ocupacion', $resultado);
        $this->assertEquals(40, $resultado['capacidad_total']);
        $this->assertEquals(40, $resultado['asientos_disponibles']);
    }
}
