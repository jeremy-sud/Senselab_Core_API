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
            'nombre' => $this->faker->unique()->randomElement(['Activo', 'Pasivo', 'Capital', 'Ingreso', 'Gasto']),
            'descripcion' => $this->faker->optional()->sentence(),
            'naturaleza' => $this->faker->randomElement(['Deudora', 'Acreedora']),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
