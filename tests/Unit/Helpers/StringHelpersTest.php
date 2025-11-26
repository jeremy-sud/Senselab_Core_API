<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use Illuminate\Support\Str;

class StringHelpersTest extends TestCase
{
    /** @test */
    public function puede_convertir_slug()
    {
        $resultado = Str::slug('Hola Mundo Test');
        $this->assertEquals('hola-mundo-test', $resultado);
    }

    /** @test */
    public function puede_convertir_a_mayusculas()
    {
        $resultado = Str::upper('hola mundo');
        $this->assertEquals('HOLA MUNDO', $resultado);
    }

    /** @test */
    public function puede_convertir_a_minusculas()
    {
        $resultado = Str::lower('HOLA MUNDO');
        $this->assertEquals('hola mundo', $resultado);
    }

    /** @test */
    public function puede_generar_uuid()
    {
        $uuid = Str::uuid()->toString();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    /** @test */
    public function puede_limitar_caracteres()
    {
        $resultado = Str::limit('Este es un texto muy largo', 10);
        $this->assertStringContainsString('...', $resultado);
    }

    /** @test */
    public function puede_verificar_inicio_de_string()
    {
        $this->assertTrue(Str::startsWith('Hola mundo', 'Hola'));
        $this->assertFalse(Str::startsWith('Hola mundo', 'mundo'));
    }

    /** @test */
    public function puede_verificar_fin_de_string()
    {
        $this->assertTrue(Str::endsWith('Hola mundo', 'mundo'));
        $this->assertFalse(Str::endsWith('Hola mundo', 'Hola'));
    }

    /** @test */
    public function puede_convertir_snake_case()
    {
        $resultado = Str::snake('HolaMundoTest');
        $this->assertEquals('hola_mundo_test', $resultado);
    }

    /** @test */
    public function puede_convertir_camel_case()
    {
        $resultado = Str::camel('hola_mundo_test');
        $this->assertEquals('holaMundoTest', $resultado);
    }

    /** @test */
    public function puede_verificar_contiene()
    {
        $this->assertTrue(Str::contains('Hola mundo', 'mundo'));
        $this->assertFalse(Str::contains('Hola mundo', 'test'));
    }

    /** @test */
    public function puede_reemplazar()
    {
        $resultado = Str::replace('mundo', 'test', 'Hola mundo');
        $this->assertEquals('Hola test', $resultado);
    }

    /** @test */
    public function puede_generar_random()
    {
        $random1 = Str::random(10);
        $random2 = Str::random(10);
        
        $this->assertEquals(10, strlen($random1));
        $this->assertEquals(10, strlen($random2));
        $this->assertNotEquals($random1, $random2);
    }

    /** @test */
    public function puede_pluralizar()
    {
        $this->assertEquals('products', Str::plural('product'));
        $this->assertEquals('categories', Str::plural('category'));
    }

    /** @test */
    public function puede_singularizar()
    {
        $this->assertEquals('product', Str::singular('products'));
        $this->assertEquals('category', Str::singular('categories'));
    }

    /** @test */
    public function puede_title_case()
    {
        $resultado = Str::title('hola mundo test');
        $this->assertEquals('Hola Mundo Test', $resultado);
    }
}
