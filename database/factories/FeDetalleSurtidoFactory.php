<?php

namespace Database\Factories;

use App\Models\FeDetalleSurtido;
use App\Models\FeLineaDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeDetalleSurtidoFactory extends Factory
{
    protected $model = FeDetalleSurtido::class;

    public function definition(): array
    {
        $cantidad = $this->faker->randomFloat(3, 1, 50);
        $precioUnitario = $this->faker->randomFloat(5, 100, 10000);
        $montoTotal = round($cantidad * $precioUnitario, 5);
        $descuento = 0;
        $subtotal = $montoTotal;

        return [
            'linea_detalle_id' => FeLineaDetalle::factory(),
            'numero_linea_surtido' => 1,
            'codigo_cabys_surtido' => $this->faker->numerify('#############'),
            'cantidad_surtido' => $cantidad,
            'unidad_medida_surtido' => 'Unid',
            'detalle_surtido' => $this->faker->sentence(3),
            'precio_unitario_surtido' => $precioUnitario,
            'monto_total_surtido' => $montoTotal,
            'monto_descuento_surtido' => $descuento,
            'subtotal_surtido' => $subtotal,
        ];
    }

    public function conDescuento(): static
    {
        return $this->state(function (array $attributes) {
            $descuento = round((float) $attributes['monto_total_surtido'] * 0.1, 5);
            return [
                'monto_descuento_surtido' => $descuento,
                'subtotal_surtido' => round((float) $attributes['monto_total_surtido'] - $descuento, 5),
            ];
        });
    }
}
