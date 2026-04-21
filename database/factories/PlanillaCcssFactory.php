<?php

namespace Database\Factories;

use App\Models\PlanillaCcss;
use App\Models\Empresa;
use App\Models\PeriodoNomina;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanillaCcssFactory extends Factory
{
    protected $model = PlanillaCcss::class;

    public function definition(): array
    {
        $salarios = $this->faker->randomFloat(2, 1000000, 20000000);
        $obrera = $salarios * 0.1067;
        $patronal = $salarios * 0.2667;

        return [
            'empresa_id' => Empresa::factory(),
            'periodo_nomina_id' => PeriodoNomina::factory(),
            'periodo' => $this->faker->date('Y-m'),
            'fecha_generacion' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'fecha_presentacion' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'numero_planilla' => $this->faker->optional()->numerify('CCSS-########'),
            'total_empleados' => $this->faker->numberBetween(5, 200),
            'total_salarios' => $salarios,
            'total_cuota_obrera' => round($obrera, 2),
            'total_cuota_patronal' => round($patronal, 2),
            'total_a_pagar' => round($obrera + $patronal, 2),
            'archivo_xml' => null,
            'archivo_pdf' => null,
            'estado' => 'borrador',
            'fecha_pago' => null,
            'notas' => $this->faker->optional()->sentence(),
            'eliminado' => false,
        ];
    }
}
