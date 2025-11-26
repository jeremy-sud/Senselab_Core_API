<?php

namespace Database\Factories;

use App\Models\TipoImpuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoImpuestoFactory extends Factory
{
    protected $model = TipoImpuesto::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('##'),
            'nombre' => $this->faker->randomElement(['IVA', 'Exento', 'Reducido', 'Especial']),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
