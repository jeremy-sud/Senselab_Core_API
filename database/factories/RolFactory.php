<?php

namespace Database\Factories;

use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

class RolFactory extends Factory
{
    protected $model = Rol::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->word(),
            'slug' => $this->faker->unique()->slug(),
            'descripcion' => $this->faker->optional()->sentence(),
            'nivel' => $this->faker->numberBetween(1, 10),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
