<?php

namespace Database\Factories;

use App\Models\PagoCuentaCobrar;
use App\Models\CuentaPorCobrar;
use App\Models\FormaPago;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagoCuentaCobrarFactory extends Factory
{
    protected $model = PagoCuentaCobrar::class;

    public function definition(): array
    {
        return [
            'cuenta_por_cobrar_id' => CuentaPorCobrar::factory(),
            'forma_pago_id' => FormaPago::factory(),
            'fecha_pago' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'monto_pago' => $this->faker->randomFloat(2, 1000, 100000),
            'numero_referencia' => $this->faker->optional()->numerify('REF-########'),
            'moneda' => 'CRC',
            'observaciones' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
