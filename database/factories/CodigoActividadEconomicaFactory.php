<?php

namespace Database\Factories;

use App\Models\CodigoActividadEconomica;
use Illuminate\Database\Eloquent\Factories\Factory;

class CodigoActividadEconomicaFactory extends Factory
{
    protected $model = CodigoActividadEconomica::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('######'),
            'descripcion' => $this->faker->sentence(5),
            'tipo' => $this->faker->randomElement(['servicios', 'comercio', 'industria', 'agricultura']),
            'activo' => $this->faker->boolean(95),
        ];
    }
}
