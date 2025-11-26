<?php

namespace Database\Factories;

use App\Models\PlanillaCcss;
use App\Models\Empleado;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanillaCcssFactory extends Factory
{
    protected $model = PlanillaCcss::class;

    public function definition(): array
    {
        $salario = $this->faker->randomFloat(2, 300000, 2000000);
        
        return [
            'empleado_id' => Empleado::factory(),
            'mes' => $this->faker->numberBetween(1, 12),
            'anio' => $this->faker->numberBetween(2023, 2024),
            'salario_reportado' => $salario,
            'ccss_trabajador' => $salario * 0.1067,
            'ccss_patronal' => $salario * 0.2667,
            'total_ccss' => $salario * 0.3734,
            'estado' => $this->faker->randomElement(['pendiente', 'enviada', 'pagada']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
