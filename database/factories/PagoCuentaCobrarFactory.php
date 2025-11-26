<?php

namespace Database\Factories;

use App\Models\PagoCuentaCobrar;
use App\Models\CuentaPorCobrar;
use App\Models\Pago;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagoCuentaCobrarFactory extends Factory
{
    protected $model = PagoCuentaCobrar::class;

    public function definition(): array
    {
        return [
            'cuenta_por_cobrar_id' => CuentaPorCobrar::factory(),
            'pago_id' => Pago::factory(),
            'monto_aplicado' => $this->faker->randomFloat(2, 1000, 50000),
            'fecha_aplicacion' => $this->faker->dateTimeThisMonth(),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
