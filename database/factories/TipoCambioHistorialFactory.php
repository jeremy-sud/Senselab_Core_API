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
            'fecha' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'moneda_origen' => 'USD',
            'moneda_destino' => 'CRC',
            'tasa_compra' => $this->faker->randomFloat(5, 500, 600),
            'tasa_venta' => $this->faker->randomFloat(5, 510, 620),
            'fuente' => $this->faker->randomElement(['BCCR', 'manual']),
        ];
    }
}
