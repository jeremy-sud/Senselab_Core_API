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
            'nombre' => implode(' ', $this->faker->words(3)) . ' Almacén',
            'descripcion' => $this->faker->optional()->sentence(),
            'ubicacion' => $this->faker->optional()->address(),
            'es_principal' => $this->faker->boolean(20),
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
