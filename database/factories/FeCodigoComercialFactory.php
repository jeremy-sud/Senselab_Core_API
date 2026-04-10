<?php

namespace Database\Factories;

use App\Models\FeCodigoComercial;
use App\Models\FeLineaDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeCodigoComercialFactory extends Factory
{
    protected $model = FeCodigoComercial::class;

    public function definition(): array
    {
        return [
            'linea_detalle_id' => FeLineaDetalle::factory(),
            'orden' => 1,
            'tipo' => $this->faker->randomElement(['01', '02', '03', '04']),
            'codigo' => $this->faker->numerify('##########'),
        ];
    }

    public function ean(): static
    {
        return $this->state(fn () => [
            'tipo' => '03',
            'codigo' => $this->faker->ean13(),
        ]);
    }

    public function interno(): static
    {
        return $this->state(fn () => [
            'tipo' => '01',
            'codigo' => $this->faker->bothify('INT-####-??'),
        ]);
    }

    public function proveedor(): static
    {
        return $this->state(fn () => [
            'tipo' => '02',
            'codigo' => $this->faker->bothify('PROV-####-??'),
        ]);
    }
}
