<?php

namespace Database\Factories;

use App\Models\DetallePresupuesto;
use App\Models\Presupuesto;
use App\Models\CuentaContable;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetallePresupuestoFactory extends Factory
{
    protected $model = DetallePresupuesto::class;

    public function definition(): array
    {
        return [
            'presupuesto_id' => Presupuesto::factory(),
            'cuenta_contable_id' => CuentaContable::factory(),
            'monto_presupuestado' => $this->faker->randomFloat(2, 10000, 500000),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
