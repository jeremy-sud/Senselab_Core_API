<?php

namespace Database\Factories;

use App\Models\PeriodoNomina;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodoNominaFactory extends Factory
{
    protected $model = PeriodoNomina::class;

    public function definition(): array
    {
        $fechaInicio = $this->faker->dateTimeBetween('-1 month', 'now');
        $fechaFin = (clone $fechaInicio)->modify('+15 days');
        
        return [
            'nombre' => $this->faker->unique()->bothify('Periodo-####'),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'fecha_pago' => (clone $fechaFin)->modify('+5 days'),
            'estado' => $this->faker->randomElement(['abierto', 'cerrado', 'procesado']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
