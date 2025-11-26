<?php

namespace Database\Factories;

use App\Models\Permiso;
use Illuminate\Database\Eloquent\Factories\Factory;

class PermisoFactory extends Factory
{
    protected $model = Permiso::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->word(),
            'slug' => $this->faker->unique()->slug(),
            'descripcion' => $this->faker->optional()->sentence(),
            'modulo' => $this->faker->randomElement(['usuarios', 'productos', 'ventas', 'compras', 'reportes']),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
