<?php

namespace Database\Factories;

use App\Models\ModeloBus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModeloBusFactory extends Factory
{
    protected $model = ModeloBus::class;

    public function definition(): array
    {
        return [
            'marca' => $this->faker->randomElement(['Mercedes-Benz', 'Volvo', 'Scania', 'MAN', 'Hyundai']),
            'modelo' => $this->faker->bothify('??-####'),
            'capacidad' => $this->faker->numberBetween(20, 60),
            'tipo' => $this->faker->randomElement(['urbano', 'interurbano', 'escolar']),
            'descripcion' => $this->faker->optional()->sentence(),
        ];
    }
}
