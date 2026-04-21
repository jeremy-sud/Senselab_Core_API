<?php

namespace Database\Factories;

use App\Models\UnidadMedida;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnidadMedidaFactory extends Factory
{
    protected $model = UnidadMedida::class;

    public function definition(): array
    {
        return [
            'codigo_dgt' => $this->faker->unique()->numerify('Ud##'),
            'nombre' => $this->faker->unique()->randomElement(['Unidad', 'Kilogramo', 'Litro', 'Metro', 'Servicio']),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
