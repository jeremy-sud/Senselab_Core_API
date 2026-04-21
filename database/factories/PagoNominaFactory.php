<?php

namespace Database\Factories;

use App\Models\PagoNomina;
use App\Models\Empresa;
use App\Models\Empleado;
use App\Models\PeriodoNomina;
use App\Models\FormaPago;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagoNominaFactory extends Factory
{
    protected $model = PagoNomina::class;

    public function definition(): array
    {
        $bruto = $this->faker->randomFloat(2, 350000, 3000000);
        $deducciones = $bruto * 0.15;

        return [
            'empresa_id' => Empresa::factory(),
            'empleado_id' => Empleado::factory(),
            'periodo_nomina_id' => PeriodoNomina::factory(),
            'fecha_pago' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'monto_bruto' => $bruto,
            'total_deducciones' => round($deducciones, 2),
            'monto_neto_pagado' => round($bruto - $deducciones, 2),
            'metodo_pago_id' => FormaPago::factory(),
            'referencia_pago' => $this->faker->optional()->numerify('TRF-########'),
            'estado' => 'pagado',
            'observaciones' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
