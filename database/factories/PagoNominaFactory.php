<?php

namespace Database\Factories;

use App\Models\PagoNomina;
use App\Models\NominaEmpleado;
use App\Models\Pago;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagoNominaFactory extends Factory
{
    protected $model = PagoNomina::class;

    public function definition(): array
    {
        return [
            'nomina_empleado_id' => NominaEmpleado::factory(),
            'pago_id' => Pago::factory(),
            'monto_pagado' => $this->faker->randomFloat(2, 100000, 1500000),
            'fecha_pago' => $this->faker->dateTimeThisMonth(),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
