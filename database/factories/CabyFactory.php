<?php

namespace Database\Factories;

use App\Models\Caby;
use Illuminate\Database\Eloquent\Factories\Factory;

class CabyFactory extends Factory
{
    protected $model = Caby::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('########'),
            'categoria' => $this->faker->numberBetween(1, 10),
            'subcategoria' => $this->faker->numberBetween(1, 99),
            'clase' => $this->faker->numberBetween(1, 99),
            'subclase' => $this->faker->numberBetween(1, 99),
            'descripcion' => $this->faker->words(5, true),
            'impuesto' => $this->faker->randomFloat(2, 0, 13),
            'activo' => $this->faker->boolean(95),
        ];
    }

    public function servicios(): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => $this->faker->numberBetween(1, 5),
            'descripcion' => 'Servicio de ' . $this->faker->words(3, true),
        ]);
    }

    public function mercancias(): static
    {
        return $this->state(fn (array $attributes) => [
            'categoria' => $this->faker->numberBetween(6, 10),
            'descripcion' => 'Mercancía ' . $this->faker->words(3, true),
        ]);
    }
}
