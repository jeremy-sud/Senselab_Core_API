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
        $cantidad = $this->faker->numberBetween(1, 100);
        $precioUnitario = $this->faker->randomFloat(2, 100, 10000);
        $subtotal = $cantidad * $precioUnitario;
        $impuesto = $subtotal * 0.13;
        
        return [
            'orden_compra_id' => OrdenCompra::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal,
            'impuesto' => $impuesto,
            'total' => $subtotal + $impuesto,
        ];
    }
}
