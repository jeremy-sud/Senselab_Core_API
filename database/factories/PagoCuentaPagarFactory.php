<?php

namespace Database\Factories;

use App\Models\PagoCuentaPagar;
use App\Models\CuentaPorPagar;
use App\Models\FormaPago;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagoCuentaPagarFactory extends Factory
{
    protected $model = PagoCuentaPagar::class;

    public function definition(): array
    {
        return [
            'cuenta_por_pagar_id' => CuentaPorPagar::factory(),
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
