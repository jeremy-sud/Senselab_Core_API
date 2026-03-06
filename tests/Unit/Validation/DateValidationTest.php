<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;

class DateValidationTest extends TestCase
{
    #[Test]
    public function puede_parsear_fecha_valida()
    {
        $fecha = Carbon::parse('2025-11-26');
        $this->assertInstanceOf(Carbon::class, $fecha);
    }

    #[Test]
    public function puede_comparar_fechas()
    {
        $fecha1 = Carbon::parse('2025-11-26');
        $fecha2 = Carbon::parse('2025-11-27');
        
        $this->assertTrue($fecha1->lt($fecha2));
        $this->assertTrue($fecha2->gt($fecha1));
    }

    #[Test]
    public function puede_formatear_fecha()
    {
        $fecha = Carbon::parse('2025-11-26');
        $this->assertEquals('2025-11-26', $fecha->format('Y-m-d'));
    }

    #[Test]
    public function puede_obtener_fecha_actual()
    {
        $now = Carbon::now();
        $this->assertInstanceOf(Carbon::class, $now);
    }

    #[Test]
    public function puede_sumar_dias()
    {
        $fecha = Carbon::parse('2025-11-26');
        $nueva = $fecha->copy()->addDays(5);
        
        $this->assertEquals('2025-12-01', $nueva->format('Y-m-d'));
    }

    #[Test]
    public function puede_restar_dias()
    {
        $fecha = Carbon::parse('2025-11-26');
        $nueva = $fecha->copy()->subDays(5);
        
        $this->assertEquals('2025-11-21', $nueva->format('Y-m-d'));
    }

    #[Test]
    public function puede_obtener_diferencia_en_dias()
    {
        $fecha1 = Carbon::parse('2025-11-26');
        $fecha2 = Carbon::parse('2025-11-30');
        
        $this->assertEquals(4, $fecha1->diffInDays($fecha2));
    }

    #[Test]
    public function puede_verificar_si_es_hoy()
    {
        $hoy = Carbon::today();
        $this->assertTrue($hoy->isToday());
    }

    #[Test]
    public function puede_verificar_si_es_pasado()
    {
        $ayer = Carbon::yesterday();
        $this->assertTrue($ayer->isPast());
    }

    #[Test]
    public function puede_verificar_si_es_futuro()
    {
        $manana = Carbon::tomorrow();
        $this->assertTrue($manana->isFuture());
    }

    #[Test]
    public function puede_obtener_inicio_de_mes()
    {
        $fecha = Carbon::parse('2025-11-26');
        $inicio = $fecha->copy()->startOfMonth();
        
        $this->assertEquals('2025-11-01', $inicio->format('Y-m-d'));
    }

    #[Test]
    public function puede_obtener_fin_de_mes()
    {
        $fecha = Carbon::parse('2025-11-26');
        $fin = $fecha->copy()->endOfMonth();
        
        $this->assertEquals('2025-11-30', $fin->format('Y-m-d'));
    }

    #[Test]
    public function puede_parsear_diferentes_formatos()
    {
        $fecha1 = Carbon::createFromFormat('d/m/Y', '26/11/2025');
        $fecha2 = Carbon::createFromFormat('d/m/Y', '26/11/2025');
        
        $this->assertEquals($fecha1->format('Y-m-d'), $fecha2->format('Y-m-d'));
    }

    #[Test]
    public function puede_crear_desde_timestamp()
    {
        $timestamp = 1732579200; // 2024-11-26
        $fecha = Carbon::createFromTimestamp($timestamp);
        
        $this->assertInstanceOf(Carbon::class, $fecha);
    }

    #[Test]
    public function puede_obtener_nombre_mes()
    {
        $fecha = Carbon::parse('2025-11-26');
        $this->assertEquals('November', $fecha->format('F'));
    }
}
