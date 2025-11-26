<?php

namespace Database\Factories;

use App\Models\DetalleVenta;
use App\Models\Venta;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleVentaFactory extends Factory
{
    protected $model = DetalleVenta::class;

    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 20);
        $precioUnitario = $this->faker->randomFloat(2, 500, 50000);
        $subtotal = $cantidad * $precioUnitario;
        $impuesto = $subtotal * 0.13;
        
        return [
            'venta_id' => Venta::factory(),
            'producto_id' => Producto::factory(),
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'subtotal' => $subtotal,
            'impuesto' => $impuesto,
            'total' => $subtotal + $impuesto,
            'descuento' => $this->faker->randomFloat(2, 0, $subtotal * 0.1),
        ];
    }
}
