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
        $costo = $this->faker->randomFloat(2, 100, 10000);
        $impPct = $this->faker->randomElement([0, 1, 2, 4, 13]);
        $subtotal = $cantidad * $costo;
        $impMonto = $subtotal * $impPct / 100;

        return [
            'entrada_inventario_id' => EntradaInventario::factory(),
            'producto_id' => Producto::factory(),
            'numero_linea' => $this->faker->numberBetween(1, 20),
            'cantidad' => $cantidad,
            'costo_unitario' => $costo,
            'subtotal' => $subtotal,
            'porcentaje_impuesto' => $impPct,
            'monto_impuesto' => $impMonto,
            'total_linea' => $subtotal + $impMonto,
            'lote' => $this->faker->optional()->numerify('LOT-####'),
            'fecha_vencimiento' => $this->faker->optional()->dateTimeBetween('+1 month', '+2 years'),
            'observaciones' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
