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
            'codigo' => $this->faker->unique()->numerify('##'),
            'nombre' => $this->faker->randomElement(['Regular', 'Mayorista', 'VIP', 'Corporativo']),
            'descripcion' => $this->faker->optional()->sentence(),
            'descuento_porcentaje' => $this->faker->randomFloat(2, 0, 20),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
