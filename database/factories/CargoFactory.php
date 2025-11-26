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
            'nombre' => $this->faker->jobTitle(),
            'descripcion' => $this->faker->optional()->sentence(10),
            'nivel_jerarquico' => $this->faker->numberBetween(1, 5),
            'salario_base' => $this->faker->randomFloat(2, 300000, 2000000),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
