<?php

namespace Database\Factories;

use App\Models\DetallePresupuesto;
use App\Models\Presupuesto;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetallePresupuestoFactory extends Factory
{
    protected $model = DetallePresupuesto::class;

    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 50);
        $precioUnitario = $this->faker->randomFloat(2, 500, 50000);
        $subtotal = $cantidad * $precioUnitario;
        $impuesto = $subtotal * 0.13;
        
        return [
            'presupuesto_id' => Presupuesto::factory(),
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
