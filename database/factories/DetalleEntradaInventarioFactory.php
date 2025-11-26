<?php

namespace Database\Factories;

use App\Models\DetalleEntradaInventario;
use App\Models\EntradaInventario;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleEntradaInventarioFactory extends Factory
{
    protected $model = DetalleEntradaInventario::class;

    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 100);
        $precioUnitario = $this->faker->randomFloat(2, 100, 10000);
        
        return [
            'entrada_inventario_id' => EntradaInventario::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $cantidad * $precioUnitario,
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
