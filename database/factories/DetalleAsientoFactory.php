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
        return [
            'asiento_contable_id' => AsientoContable::factory(),
            'cuenta_contable_id' => CuentaContable::factory(),
            'debe' => $this->faker->randomFloat(2, 0, 50000),
            'haber' => 0,
            'descripcion' => $this->faker->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
