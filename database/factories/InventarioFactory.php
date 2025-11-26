<?php

namespace Database\Factories;

use App\Models\Inventario;
use App\Models\Almacen;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventarioFactory extends Factory
{
    protected $model = Inventario::class;

    public function definition(): array
    {
        return [
            'almacen_id' => Almacen::factory(),
            'producto_id' => Producto::factory(),
            'cantidad_actual' => $this->faker->numberBetween(0, 1000),
            'cantidad_reservada' => $this->faker->numberBetween(0, 50),
            'cantidad_disponible' => $this->faker->numberBetween(0, 950),
            'stock_minimo' => $this->faker->numberBetween(5, 50),
            'stock_maximo' => $this->faker->numberBetween(100, 1000),
            'ultima_actualizacion' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
