<?php

namespace Database\Factories;

use App\Models\DeclaracionTributaria;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeclaracionTributariaFactory extends Factory
{
    protected $model = DeclaracionTributaria::class;

    public function definition(): array
    {
        $periodo = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m');
        $year = substr($periodo, 0, 4);
        $month = substr($periodo, 5, 2);
        
        $montoBase = $this->faker->randomFloat(2, 100000, 5000000);
        $montoImpuesto = $montoBase * 0.13;
        $montoCreditos = $this->faker->randomFloat(2, 10000, $montoImpuesto);
        $montoDebitos = $montoImpuesto;
        $montoPagar = max($montoDebitos - $montoCreditos, 0);
        $montoFavor = max($montoCreditos - $montoDebitos, 0);

        return [
            'empresa_id' => Empresa::factory(),
            'tipo_declaracion' => $this->faker->randomElement(['D104', 'D101', 'D103', 'D150', 'D151']),
            'periodo_fiscal' => $periodo,
            'fecha_inicio_periodo' => "{$year}-{$month}-01",
            'fecha_fin_periodo' => date('Y-m-t', strtotime("{$year}-{$month}-01")),
            'fecha_presentacion' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
            'monto_base_imponible' => $montoBase,
            'monto_impuesto' => $montoImpuesto,
            'monto_creditos' => $montoCreditos,
            'monto_debitos' => $montoDebitos,
            'monto_a_pagar' => $montoPagar,
            'monto_a_favor' => $montoFavor,
            'numero_confirmacion' => $this->faker->optional()->numerify('####-####-####-####'),
            'estado' => $this->faker->randomElement(['borrador', 'enviada', 'aceptada', 'rechazada']),
            'eliminado' => false,
        ];
    }
}
