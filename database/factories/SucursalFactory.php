<?php

namespace Database\Factories;

use App\Models\Sucursal;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class SucursalFactory extends Factory
{
    protected $model = Sucursal::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => 'Sucursal ' . $this->faker->city(),
            'direccion' => $this->faker->address(),
            'telefono' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'es_principal' => false,
            'activo' => true,
            'eliminado' => false,
        ];
    }

    public function principal(): static
    {
        return $this->state(fn (array $attributes) => [
            'es_principal' => true,
            'nombre' => 'Sucursal Principal',
        ]);
    }
}
