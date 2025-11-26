<?php

namespace Database\Factories;

use App\Models\ConfiguracionApi;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConfiguracionApiFactory extends Factory
{
    protected $model = ConfiguracionApi::class;

    public function definition(): array
    {
        return [
            'clave' => $this->faker->unique()->slug(),
            'valor' => $this->faker->word(),
            'tipo' => $this->faker->randomElement(['string', 'number', 'boolean', 'json']),
            'categoria' => $this->faker->randomElement(['general', 'email', 'sms', 'facturacion', 'seguridad']),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
