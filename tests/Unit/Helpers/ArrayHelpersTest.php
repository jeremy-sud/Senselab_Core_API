<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;

class ArrayHelpersTest extends TestCase
{
    #[Test]
    public function puede_obtener_valor_con_dot_notation()
    {
        $array = ['user' => ['name' => 'John']];
        $resultado = Arr::get($array, 'user.name');
        $this->assertEquals('John', $resultado);
    }

    #[Test]
    public function puede_verificar_si_existe_clave()
    {
        $array = ['name' => 'John', 'age' => 30];
        $this->assertTrue(Arr::exists($array, 'name'));
        $this->assertFalse(Arr::exists($array, 'email'));
    }

    #[Test]
    public function puede_obtener_solo_claves_especificas()
    {
        $array = ['name' => 'John', 'age' => 30, 'email' => 'john@test.com'];
        $resultado = Arr::only($array, ['name', 'email']);
        
        $this->assertArrayHasKey('name', $resultado);
        $this->assertArrayHasKey('email', $resultado);
        $this->assertArrayNotHasKey('age', $resultado);
    }

    #[Test]
    public function puede_excluir_claves_especificas()
    {
        $array = ['name' => 'John', 'age' => 30, 'email' => 'john@test.com'];
        $resultado = Arr::except($array, ['age']);
        
        $this->assertArrayHasKey('name', $resultado);
        $this->assertArrayHasKey('email', $resultado);
        $this->assertArrayNotHasKey('age', $resultado);
    }

    #[Test]
    public function puede_aplanar_array()
    {
        $array = ['name' => 'John', 'languages' => ['php', 'javascript']];
        $resultado = Arr::flatten($array);
        
        $this->assertContains('John', $resultado);
        $this->assertContains('php', $resultado);
        $this->assertContains('javascript', $resultado);
    }

    #[Test]
    public function puede_agregar_elemento_al_inicio()
    {
        $array = [2, 3, 4];
        $resultado = Arr::prepend($array, 1);
        
        $this->assertEquals(1, $resultado[0]);
        $this->assertCount(4, $resultado);
    }

    #[Test]
    public function puede_obtener_primero_que_cumple_condicion()
    {
        $array = [1, 2, 3, 4, 5];
        $resultado = Arr::first($array, fn($value) => $value > 3);
        
        $this->assertEquals(4, $resultado);
    }

    #[Test]
    public function puede_obtener_ultimo_que_cumple_condicion()
    {
        $array = [1, 2, 3, 4, 5];
        $resultado = Arr::last($array, fn($value) => $value < 4);
        
        $this->assertEquals(3, $resultado);
    }

    #[Test]
    public function puede_verificar_si_es_array_asociativo()
    {
        $this->assertTrue(Arr::isAssoc(['name' => 'John', 'age' => 30]));
        $this->assertFalse(Arr::isAssoc([1, 2, 3]));
    }

    #[Test]
    public function puede_dividir_array()
    {
        $array = [1, 2, 3, 4, 5, 6];
        $resultado = Arr::divide($array);
        
        $this->assertCount(2, $resultado);
        $this->assertIsArray($resultado[0]); // keys
        $this->assertIsArray($resultado[1]); // values
    }

    #[Test]
    public function puede_obtener_random()
    {
        $array = [1, 2, 3, 4, 5];
        $resultado = Arr::random($array);
        
        $this->assertContains($resultado, $array);
    }

    #[Test]
    public function puede_envolver_valor_en_array()
    {
        $resultado1 = Arr::wrap('test');
        $resultado2 = Arr::wrap(['test']);
        
        $this->assertEquals(['test'], $resultado1);
        $this->assertEquals(['test'], $resultado2);
    }

    #[Test]
    public function puede_obtener_claves()
    {
        $array = ['name' => 'John', 'age' => 30];
        $keys = array_keys($array);
        
        $this->assertContains('name', $keys);
        $this->assertContains('age', $keys);
    }

    #[Test]
    public function puede_obtener_valores()
    {
        $array = ['name' => 'John', 'age' => 30];
        $values = array_values($array);
        
        $this->assertContains('John', $values);
        $this->assertContains(30, $values);
    }

    #[Test]
    public function puede_combinar_arrays()
    {
        $array1 = ['name' => 'John'];
        $array2 = ['age' => 30];
        $resultado = array_merge($array1, $array2);
        
        $this->assertArrayHasKey('name', $resultado);
        $this->assertArrayHasKey('age', $resultado);
    }
}
