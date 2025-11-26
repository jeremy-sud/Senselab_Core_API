<?php

namespace Database\Factories;

use App\Models\TipoCuenta;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoCuentaFactory extends Factory
{
    protected $model = TipoCuenta::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('##'),
            'nombre' => $this->faker->randomElement(['Activo', 'Pasivo', 'Patrimonio', 'Ingreso', 'Gasto']),
            'descripcion' => $this->faker->optional()->sentence(),
            'naturaleza' => $this->faker->randomElement(['deudora', 'acreedora']),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
