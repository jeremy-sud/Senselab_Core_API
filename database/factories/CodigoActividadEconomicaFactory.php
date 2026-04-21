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
            'descripcion' => $this->faker->sentence(),
            'categoria_principal' => $this->faker->randomElement(['Comercio', 'Servicios', 'Industria']),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
