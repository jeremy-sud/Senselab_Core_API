<?php

namespace Database\Factories;

use App\Models\Pago;
use App\Models\FormaPago;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagoFactory extends Factory
{
    protected $model = Pago::class;

    public function definition(): array
    {
        return [
            'forma_pago_id' => FormaPago::factory(),
            'monto' => $this->faker->randomFloat(2, 1000, 500000),
            'fecha_pago' => $this->faker->dateTimeThisMonth(),
            'numero_referencia' => $this->faker->optional()->numerify('REF-########'),
            'comprobante' => $this->faker->optional()->numerify('COMP-####'),
            'usuario_id' => Usuario::factory(),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
