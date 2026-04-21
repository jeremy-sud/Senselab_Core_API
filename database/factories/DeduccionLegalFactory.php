<?php

namespace Database\Factories;

use App\Models\DeduccionLegal;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeduccionLegalFactory extends Factory
{
    protected $model = DeduccionLegal::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('DED-###'),
            'nombre' => $this->faker->unique()->words(3, true),
            'descripcion' => $this->faker->optional()->sentence(),
            'tipo' => $this->faker->randomElement(['patronal', 'obrera']),
            'porcentaje_base' => $this->faker->randomFloat(2, 0.5, 15),
            'monto_fijo' => 0,
            'aplica_sobre' => $this->faker->randomElement(['salario_bruto', 'salario_neto']),
            'es_obligatoria' => true,
            'activa' => true,
            'eliminado' => false,
        ];
    }
}
