<?php

namespace Database\Factories;

use App\Models\TasaImpuesto;
use App\Models\TipoImpuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

class TasaImpuestoFactory extends Factory
{
    protected $model = TasaImpuesto::class;

    public function definition(): array
    {
        return [
            'tipo_impuesto_id' => TipoImpuesto::factory(),
            'porcentaje' => $this->faker->randomElement([0, 1, 2, 4, 8, 13]),
            'fecha_inicio' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'fecha_fin' => null,
            'activo' => $this->faker->boolean(90),
        ];
    }
}
