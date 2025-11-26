<?php

namespace Database\Factories;

use App\Models\TipoCambioHistorial;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoCambioHistorialFactory extends Factory
{
    protected $model = TipoCambioHistorial::class;

    public function definition(): array
    {
        return [
            'moneda' => $this->faker->randomElement(['USD', 'EUR']),
            'fecha' => $this->faker->dateTimeThisMonth(),
            'compra' => $this->faker->randomFloat(2, 500, 600),
            'venta' => $this->faker->randomFloat(2, 510, 610),
            'fuente' => $this->faker->randomElement(['BCCR', 'Manual']),
        ];
    }
}
