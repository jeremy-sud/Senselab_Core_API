<?php

namespace Database\Factories;

use App\Models\InventarioProducto;
use App\Models\Producto;
use App\Models\Almacen;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventarioProductoFactory extends Factory
{
    protected $model = InventarioProducto::class;

    public function definition(): array
    {
        return [
            'almacen_id' => Almacen::factory(),
            'producto_id' => Producto::factory(),
            'stock_actual' => $this->faker->numberBetween(0, 1000),
            'costo_promedio' => $this->faker->randomFloat(2, 100, 10000),
            'stock_minimo' => $this->faker->numberBetween(1, 50),
            'stock_maximo' => $this->faker->numberBetween(100, 5000),
            'ubicacion' => $this->faker->optional()->numerify('A-##-##'),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
