<?php

namespace Database\Factories;

use App\Models\DetalleSalidaInventario;
use App\Models\SalidaInventario;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleSalidaInventarioFactory extends Factory
{
    protected $model = DetalleSalidaInventario::class;

    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 50);
        $precioUnitario = $this->faker->randomFloat(2, 100, 10000);
        
        return [
            'salida_inventario_id' => SalidaInventario::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $cantidad * $precioUnitario,
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
