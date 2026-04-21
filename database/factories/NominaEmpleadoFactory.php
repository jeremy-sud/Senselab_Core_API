<?php

namespace Database\Factories;

use App\Models\NominaEmpleado;
use App\Models\PeriodoNomina;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Factories\Factory;

class NominaEmpleadoFactory extends Factory
{
    protected $model = NominaEmpleado::class;

    public function definition(): array
    {
        $salario = $this->faker->randomFloat(2, 350000, 3000000);
        $horas = $this->faker->randomFloat(2, 0, 20);
        $montoHoras = $horas * ($salario / 240) * 1.5;
        $bonif = $this->faker->randomFloat(2, 0, 50000);
        $devengado = $salario + $montoHoras + $bonif;
        $ccss = $devengado * 0.1067;
        $renta = $devengado > 929000 ? ($devengado - 929000) * 0.10 : 0;
        $otras = $this->faker->randomFloat(2, 0, 10000);

        return [
            'periodo_nomina_id' => PeriodoNomina::factory(),
            'empleado_id' => Empleado::factory(),
            'salario_bruto' => $salario,
            'horas_extras' => $horas,
            'monto_horas_extras' => round($montoHoras, 2),
            'bonificaciones' => $bonif,
            'total_devengado' => round($devengado, 2),
            'deducciones_ccss' => round($ccss, 2),
            'deducciones_impuesto_renta' => round($renta, 2),
            'otras_deducciones' => $otras,
            'total_deducciones' => round($ccss + $renta + $otras, 2),
            'salario_neto' => round($devengado - $ccss - $renta - $otras, 2),
            'observaciones' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
