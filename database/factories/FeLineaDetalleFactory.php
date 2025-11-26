<?php

namespace Database\Factories;

use App\Models\FeLineaDetalle;
use App\Models\ComprobanteElectronicoFe;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeLineaDetalleFactory extends Factory
{
    protected $model = FeLineaDetalle::class;

    public function definition(): array
    {
        $cantidad = $this->faker->randomFloat(5, 1, 100);
        $precioUnitario = $this->faker->randomFloat(5, 100, 50000);
        $montoTotal = $cantidad * $precioUnitario;
        $descuento = $montoTotal * 0.05; // 5% descuento
        $subtotal = $montoTotal - $descuento;
        $impuesto = $subtotal * 0.13; // 13% IVA
        $montoTotalLinea = $subtotal + $impuesto;

        return [
            'comprobante_id' => ComprobanteElectronicoFe::factory(),
            'numero_linea' => 1,
            'codigo_tipo' => '01',
            'codigo' => $this->faker->numerify('85231021#####'),
            'cantidad' => $cantidad,
            'unidad_medida' => 'Sp',
            'detalle' => $this->faker->sentence(3),
            'precio_unitario' => $precioUnitario,
            'monto_total' => $montoTotal,
            'monto_descuento' => $descuento,
            'naturaleza_descuento' => 'Descuento comercial',
            'subtotal' => $subtotal,
            'base_imponible' => $subtotal,
            'impuestos' => [
                [
                    'codigo' => '01',
                    'codigo_tarifa' => '08',
                    'tarifa' => 13.00,
                    'monto' => round($impuesto, 5),
                ]
            ],
            'monto_total_linea' => $montoTotalLinea,
        ];
    }

    public function sinDescuento(): static
    {
        return $this->state(function (array $attributes) {
            $cantidad = $attributes['cantidad'];
            $precioUnitario = $attributes['precio_unitario'];
            $montoTotal = $cantidad * $precioUnitario;
            $impuesto = $montoTotal * 0.13;
            
            return [
                'monto_descuento' => 0,
                'subtotal' => $montoTotal,
                'base_imponible' => $montoTotal,
                'monto_total_linea' => $montoTotal + $impuesto,
            ];
        });
    }
}
