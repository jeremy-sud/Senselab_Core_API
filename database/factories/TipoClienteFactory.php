<?php

namespace Database\Factories;

use App\Models\TipoCliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoClienteFactory extends Factory
{
    protected $model = TipoCliente::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('TC-###'),
            'nombre' => $this->faker->unique()->words(2, true),
            'descripcion' => $this->faker->optional()->sentence(),
            'descuento_default' => $this->faker->randomFloat(2, 0, 25),
            'dias_credito_default' => $this->faker->randomElement([0, 15, 30, 60]),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
