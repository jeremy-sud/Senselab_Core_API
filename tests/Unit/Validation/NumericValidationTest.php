<?php

namespace Tests\Unit\Validation;

use Tests\TestCase;

class NumericValidationTest extends TestCase
{
    /** @test */
    public function numero_entero_es_numerico()
    {
        $this->assertTrue(is_numeric(123));
        $this->assertTrue(is_numeric('123'));
    }

    /** @test */
    public function numero_decimal_es_numerico()
    {
        $this->assertTrue(is_numeric(123.45));
        $this->assertTrue(is_numeric('123.45'));
    }

    /** @test */
    public function numero_negativo_es_numerico()
    {
        $this->assertTrue(is_numeric(-123));
        $this->assertTrue(is_numeric('-123'));
    }

    /** @test */
    public function string_no_numerico_falla()
    {
        $this->assertFalse(is_numeric('abc'));
        $this->assertFalse(is_numeric('12abc'));
    }

    /** @test */
    public function puede_verificar_entero()
    {
        $this->assertTrue(is_int(123));
        $this->assertFalse(is_int('123'));
        $this->assertFalse(is_int(123.45));
    }

    /** @test */
    public function puede_verificar_float()
    {
        $this->assertTrue(is_float(123.45));
        $this->assertFalse(is_float(123));
        $this->assertFalse(is_float('123.45'));
    }

    /** @test */
    public function puede_formatear_decimal()
    {
        $numero = 1234.5678;
        $resultado = number_format($numero, 2);
        $this->assertEquals('1,234.57', $resultado);
    }

    /** @test */
    public function puede_redondear_numero()
    {
        $this->assertEquals(124, round(123.5));
        $this->assertEquals(123, round(123.4));
    }

    /** @test */
    public function puede_redondear_hacia_arriba()
    {
        $this->assertEquals(124, ceil(123.1));
        $this->assertEquals(124, ceil(123.9));
    }

    /** @test */
    public function puede_redondear_hacia_abajo()
    {
        $this->assertEquals(123, floor(123.1));
        $this->assertEquals(123, floor(123.9));
    }

    /** @test */
    public function puede_obtener_valor_absoluto()
    {
        $this->assertEquals(123, abs(-123));
        $this->assertEquals(123.45, abs(-123.45));
    }

    /** @test */
    public function puede_obtener_maximo()
    {
        $this->assertEquals(5, max(1, 2, 3, 4, 5));
        $this->assertEquals(5, max([1, 2, 3, 4, 5]));
    }

    /** @test */
    public function puede_obtener_minimo()
    {
        $this->assertEquals(1, min(1, 2, 3, 4, 5));
        $this->assertEquals(1, min([1, 2, 3, 4, 5]));
    }

    /** @test */
    public function puede_calcular_suma()
    {
        $numeros = [1, 2, 3, 4, 5];
        $this->assertEquals(15, array_sum($numeros));
    }

    /** @test */
    public function puede_generar_random()
    {
        $random = rand(1, 100);
        $this->assertGreaterThanOrEqual(1, $random);
        $this->assertLessThanOrEqual(100, $random);
    }
}
