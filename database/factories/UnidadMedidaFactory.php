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
            'codigo' => $this->faker->unique()->bothify('??##'),
            'nombre' => $this->faker->randomElement(['Unidad', 'Kilo', 'Litro', 'Metro', 'Caja', 'Docena']),
            'descripcion' => $this->faker->optional()->sentence(),
            'simbolo' => $this->faker->randomElement(['Und', 'Kg', 'L', 'm', 'Cj', 'Dz']),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
