<?php

namespace Database\Factories;

use App\Models\Etiqueta;
use Illuminate\Database\Eloquent\Factories\Factory;

class EtiquetaFactory extends Factory
{
    protected $model = Etiqueta::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->word(),
            'slug' => fn (array $attributes) => \Str::slug($attributes['nombre']),
            'color' => $this->faker->hexColor(),
            'descripcion' => $this->faker->optional()->sentence(),
            'icono' => $this->faker->optional()->randomElement(['tag', 'star', 'flag', 'bookmark']),
        ];
    }
}
