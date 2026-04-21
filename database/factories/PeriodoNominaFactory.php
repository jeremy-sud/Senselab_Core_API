<?php

namespace Database\Factories;

use App\Models\PeriodoNomina;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class PeriodoNominaFactory extends Factory
{
    protected $model = PeriodoNomina::class;

    public function definition(): array
    {
        $inicio = $this->faker->dateTimeBetween('-6 months', 'now');

        return [
            'empresa_id' => Empresa::factory(),
            'nombre_periodo' => 'Quincena ' . $this->faker->monthName() . ' ' . $this->faker->year(),
            'fecha_inicio' => $inicio,
            'fecha_fin' => (clone $inicio)->modify('+15 days'),
            'fecha_pago' => (clone $inicio)->modify('+20 days'),
            'estado' => $this->faker->randomElement(['abierto', 'cerrado', 'pagado']),
            'total_salarios' => $this->faker->randomFloat(2, 500000, 10000000),
            'total_deducciones' => $this->faker->randomFloat(2, 50000, 1500000),
            'total_neto' => $this->faker->randomFloat(2, 400000, 8500000),
            'observaciones' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
