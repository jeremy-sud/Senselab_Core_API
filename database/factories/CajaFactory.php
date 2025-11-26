<?php

namespace Database\Factories;

use App\Models\Caja;
use App\Models\Empresa;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

class CajaFactory extends Factory
{
    protected $model = Caja::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'sucursal_id' => Sucursal::factory(),
            'nombre' => 'Caja ' . $this->faker->numberBetween(1, 20),
            'codigo' => strtoupper($this->faker->unique()->lexify('CAJ-???')),
            'descripcion' => $this->faker->optional()->sentence(),
            'saldo_inicial' => $this->faker->randomFloat(2, 1000, 10000),
            'saldo_actual' => $this->faker->randomFloat(2, 500, 15000),
            'activo' => $this->faker->boolean(85),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }

    public function activa(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => true,
        ]);
    }
}
