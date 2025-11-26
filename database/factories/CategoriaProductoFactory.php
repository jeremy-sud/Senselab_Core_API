<?php

namespace Database\Factories;

use App\Models\CategoriaProducto;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoriaProductoFactory extends Factory
{
    protected $model = CategoriaProducto::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->words(2, true),
            'descripcion' => $this->faker->optional()->sentence(),
            'codigo' => strtoupper($this->faker->unique()->lexify('CAT-???')),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
