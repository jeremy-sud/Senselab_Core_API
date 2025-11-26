<?php

namespace Database\Factories;

use App\Models\RegimenTributario;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegimenTributarioFactory extends Factory
{
    protected $model = RegimenTributario::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('##'),
            'nombre' => $this->faker->randomElement(['Tradicional', 'Simplificado', 'Especial']),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
