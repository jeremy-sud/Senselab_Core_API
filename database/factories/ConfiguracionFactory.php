<?php

namespace Database\Factories;

use App\Models\Configuracion;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConfiguracionFactory extends Factory
{
    protected $model = Configuracion::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'clave' => $this->faker->unique()->slug(2),
            'valor' => $this->faker->word(),
            'tipo' => $this->faker->randomElement(['string', 'number', 'boolean', 'json']),
            'descripcion' => $this->faker->optional()->sentence(),
            'categoria' => $this->faker->randomElement(['general', 'facturacion', 'inventario', 'sistema']),
            'es_publica' => $this->faker->boolean(30),
        ];
    }
}
