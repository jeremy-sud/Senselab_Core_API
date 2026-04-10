<?php

namespace Database\Factories;

use App\Models\FeDetalleSurtido;
use App\Models\FeSurtidoImpuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeSurtidoImpuestoFactory extends Factory
{
    protected $model = FeSurtidoImpuesto::class;

    public function definition(): array
    {
        $tarifa = $this->faker->randomElement([1.00, 2.00, 4.00, 8.00, 13.00]);
        $baseImponible = $this->faker->randomFloat(5, 1000, 50000);
        $monto = round($baseImponible * $tarifa / 100, 5);

        return [
            'detalle_surtido_id' => FeDetalleSurtido::factory(),
            'codigo' => '01',
            'codigo_tarifa_iva' => '08',
            'tarifa' => $tarifa,
            'monto' => $monto,
        ];
    }

    public function iva13(): static
    {
        return $this->state(fn () => [
            'codigo' => '01',
            'codigo_tarifa_iva' => '08',
            'tarifa' => 13.00,
        ]);
    }

    public function iva4(): static
    {
        return $this->state(fn () => [
            'codigo' => '01',
            'codigo_tarifa_iva' => '03',
            'tarifa' => 4.00,
        ]);
    }
}
