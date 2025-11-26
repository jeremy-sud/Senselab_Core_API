<?php

namespace Database\Factories;

use App\Models\PagoCuentaPagar;
use App\Models\CuentaPorPagar;
use App\Models\Pago;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagoCuentaPagarFactory extends Factory
{
    protected $model = PagoCuentaPagar::class;

    public function definition(): array
    {
        return [
            'cuenta_por_pagar_id' => CuentaPorPagar::factory(),
            'pago_id' => Pago::factory(),
            'monto_aplicado' => $this->faker->randomFloat(2, 1000, 50000),
            'fecha_aplicacion' => $this->faker->dateTimeThisMonth(),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
