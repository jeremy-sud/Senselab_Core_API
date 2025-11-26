<?php

namespace Database\Factories;

use App\Models\NominaEmpleado;
use App\Models\Empleado;
use App\Models\PeriodoNomina;
use Illuminate\Database\Eloquent\Factories\Factory;

class NominaEmpleadoFactory extends Factory
{
    protected $model = NominaEmpleado::class;

    public function definition(): array
    {
        $salarioBruto = $this->faker->randomFloat(2, 300000, 2000000);
        $deducciones = $salarioBruto * 0.1067; // ~10.67% CCSS
        
        return [
            'empleado_id' => Empleado::factory(),
            'periodo_nomina_id' => PeriodoNomina::factory(),
            'salario_bruto' => $salarioBruto,
            'horas_extra' => $this->faker->randomFloat(2, 0, 40),
            'total_devengado' => $salarioBruto,
            'total_deducciones' => $deducciones,
            'salario_neto' => $salarioBruto - $deducciones,
            'dias_laborados' => $this->faker->numberBetween(20, 30),
            'estado' => $this->faker->randomElement(['pendiente', 'procesada', 'pagada']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
