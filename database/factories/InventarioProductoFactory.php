<?php

namespace Database\Factories;

use App\Models\InventarioProducto;
use App\Models\Almacen;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventarioProductoFactory extends Factory
{
    protected $model = InventarioProducto::class;

    public function definition(): array
    {
        return [
            'almacen_id' => Almacen::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => $this->faker->numberBetween(0, 1000),
            'stock_minimo' => $this->faker->numberBetween(5, 50),
            'stock_maximo' => $this->faker->numberBetween(100, 500),
            'ubicacion' => $this->faker->optional()->bothify('EST-##-##'),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }

    public function bajoStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'cantidad' => $this->faker->numberBetween(0, $attributes['stock_minimo']),
        ]);
    }
}
