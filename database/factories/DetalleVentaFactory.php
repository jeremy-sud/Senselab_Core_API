<?php

namespace Database\Factories;

use App\Models\DetalleVenta;
use App\Models\Venta;
use App\Models\Producto;
use App\Models\TipoImpuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetalleVentaFactory extends Factory
{
    protected $model = DetalleVenta::class;

    public function definition(): array
    {
        $cantidad = $this->faker->numberBetween(1, 20);
        $precio = $this->faker->randomFloat(2, 500, 50000);
        $subtotal = $cantidad * $precio;
        $descPct = $this->faker->randomElement([0, 5, 10, 15]);
        $descMonto = $subtotal * $descPct / 100;
        $subtotalDesc = $subtotal - $descMonto;
        $tasaImp = $this->faker->randomElement([0, 1, 2, 4, 13]);
        $impMonto = $subtotalDesc * $tasaImp / 100;

        return [
            'venta_id' => Venta::factory(),
            'producto_id' => Producto::factory(),
            'numero_linea' => $this->faker->numberBetween(1, 20),
            'cantidad' => $cantidad,
            'precio_unitario' => $precio,
            'subtotal_linea' => $subtotal,
            'porcentaje_descuento' => $descPct,
            'monto_descuento' => $descMonto,
            'subtotal_con_descuento' => $subtotalDesc,
            'tipo_impuesto_id' => null,
            'tasa_impuesto' => $tasaImp,
            'monto_impuesto' => $impMonto,
            'total_linea' => $subtotalDesc + $impMonto,
            'detalle_adicional' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
