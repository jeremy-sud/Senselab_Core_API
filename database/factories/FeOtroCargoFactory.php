<?php

namespace Database\Factories;

use App\Models\ComprobanteElectronicoFe;
use App\Models\FeOtroCargo;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeOtroCargoFactory extends Factory
{
    protected $model = FeOtroCargo::class;

    public function definition(): array
    {
        return [
            'comprobante_id' => ComprobanteElectronicoFe::factory(),
            'tipo_documento_oc' => $this->faker->randomElement(['01', '02', '03', '06']),
            'detalle' => $this->faker->sentence(4),
            'monto_cargo' => $this->faker->randomFloat(5, 100, 10000),
        ];
    }

    public function conPorcentaje(): static
    {
        return $this->state(fn () => [
            'porcentaje_oc' => $this->faker->randomFloat(5, 1, 15),
        ]);
    }

    public function conTercero(): static
    {
        return $this->state(fn () => [
            'tipo_documento_oc' => '04',
            'tercero_tipo_identificacion' => '01',
            'tercero_numero_identificacion' => $this->faker->numerify('#########'),
            'nombre_tercero' => $this->faker->company(),
        ]);
    }
}
