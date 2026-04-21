<?php

namespace Database\Factories;

use App\Models\DetalleOrdenCompra;
use App\Models\OrdenCompra;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleOrdenCompraFactory extends Factory
{
    protected $model = DetalleOrdenCompra::class;

    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 50);
        $precio = $this->faker->randomFloat(2, 100, 10000);
        $subtotal = $cantidad * $precio;
        $impPct = $this->faker->randomElement([0, 1, 2, 4, 13]);
        $impMonto = $subtotal * $impPct / 100;

        return [
            'orden_compra_id' => OrdenCompra::factory(),
            'producto_id' => Producto::factory(),
            'numero_linea' => $this->faker->numberBetween(1, 20),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal_linea' => $subtotal,
            'porcentaje_impuesto' => $impPct,
            'monto_impuesto' => $impMonto,
            'total_linea' => $subtotal + $impMonto,
            'detalle_adicional' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
