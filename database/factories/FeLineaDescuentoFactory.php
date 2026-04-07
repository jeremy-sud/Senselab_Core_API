<?php

namespace Database\Factories;

use App\Models\FeLineaDescuento;
use App\Models\FeLineaDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeLineaDescuentoFactory extends Factory
{
    protected $model = FeLineaDescuento::class;

    public function definition(): array
    {
        return [
            'linea_detalle_id' => FeLineaDetalle::factory(),
            'orden' => 1,
            'monto_descuento' => $this->faker->randomFloat(5, 100, 5000),
            'codigo_descuento' => $this->faker->randomElement(['01', '02', '03', '04', '05', '06', '07']),
        ];
    }

    public function codigoOtro(): static
    {
        return $this->state(fn () => [
            'codigo_descuento' => '99',
            'codigo_descuento_otro' => 'Descuento especial',
            'naturaleza_descuento' => 'Descuento aplicado por acuerdo comercial',
        ]);
    }
}
