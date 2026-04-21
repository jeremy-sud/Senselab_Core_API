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
            'tasa_porcentaje' => $this->faker->randomElement([1, 2, 4, 8, 13]),
            'fecha_inicio_vigencia' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'fecha_fin_vigencia' => $this->faker->optional()->dateTimeBetween('now', '+2 years'),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
