<?php

namespace Database\Factories;

use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlmacenFactory extends Factory
{
    protected $model = Almacen::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'sucursal_id' => Sucursal::factory(),
            'codigo' => strtoupper($this->faker->unique()->lexify('ALM-???')),
            'nombre' => $this->faker->words(3, true) . ' Almacén',
            'descripcion' => $this->faker->optional()->sentence(),
            'direccion' => $this->faker->address(),
            'telefono' => $this->faker->optional()->numerify('####-####'),
            'email' => $this->faker->optional()->safeEmail(),
            'capacidad_maxima' => $this->faker->optional()->numberBetween(1000, 50000),
            'activo' => $this->faker->boolean(90),
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
