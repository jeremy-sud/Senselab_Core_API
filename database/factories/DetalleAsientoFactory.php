<?php

namespace Database\Factories;

use App\Models\DetalleAsiento;
use App\Models\AsientoContable;
use App\Models\CuentaContable;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleAsientoFactory extends Factory
{
    protected $model = DetalleAsiento::class;

    public function definition(): array
    {
        $monto = $this->faker->randomFloat(2, 1000, 100000);
        
        return [
            'asiento_contable_id' => AsientoContable::factory(),
            'cuenta_contable_id' => CuentaContable::factory(),
            'tipo_movimiento' => $this->faker->randomElement(['debe', 'haber']),
            'monto' => $monto,
            'descripcion' => $this->faker->optional()->sentence(),
        ];
    }
}
