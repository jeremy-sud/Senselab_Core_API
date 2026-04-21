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
            'codigo_hacienda' => $this->faker->unique()->numerify('##'),
            'nombre' => $this->faker->unique()->words(2, true),
            'descripcion' => $this->faker->optional()->sentence(),
            'comentario' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
