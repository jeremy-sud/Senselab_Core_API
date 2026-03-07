<?php

namespace Database\Factories;

use App\Models\Cargo;
use Illuminate\Database\Eloquent\Factories\Factory;

class CargoFactory extends Factory
{
    protected $model = Cargo::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->jobTitle(),
            'descripcion' => $this->faker->optional()->sentence(10),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
