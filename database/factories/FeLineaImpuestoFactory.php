<?php

namespace Database\Factories;

use App\Models\FeLineaDetalle;
use App\Models\FeLineaImpuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeLineaImpuestoFactory extends Factory
{
    protected $model = FeLineaImpuesto::class;

    public function definition(): array
    {
        $tarifa = $this->faker->randomElement([1.00, 2.00, 4.00, 8.00, 13.00]);
        $baseImponible = $this->faker->randomFloat(5, 1000, 50000);
        $monto = round($baseImponible * $tarifa / 100, 5);

        return [
            'linea_detalle_id' => FeLineaDetalle::factory(),
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

    public function selectivoConsumo(): static
    {
        return $this->state(fn () => [
            'codigo' => '02',
            'codigo_tarifa_iva' => null,
            'tarifa' => null,
        ]);
    }

    public function conExoneracion(): static
    {
        return $this->state(fn (array $attributes) => [
            'exoneracion_tipo_documento' => '01',
            'exoneracion_numero_documento' => $this->faker->numerify('EXO-########'),
            'exoneracion_nombre_institucion' => $this->faker->company(),
            'exoneracion_fecha_emision' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'exoneracion_tarifa_exonerada' => 13.00,
            'exoneracion_monto' => round((float) $attributes['monto'] * 0.5, 5),
        ]);
    }

    public function impuestoEspecifico(string $codigo = '04'): static
    {
        return $this->state(fn () => [
            'codigo' => $codigo,
            'codigo_tarifa_iva' => null,
            'tarifa' => null,
            'cantidad_unidad_medida' => $this->faker->randomFloat(2, 1, 100),
            'porcentaje' => $this->faker->randomFloat(2, 0.5, 25),
            'impuesto_unidad' => $this->faker->randomFloat(5, 10, 5000),
        ]);
    }
}
