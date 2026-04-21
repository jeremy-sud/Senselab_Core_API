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
            'partida_arancelaria' => null,
            'codigo_tipo' => '01',
            'codigo' => $this->faker->numerify('85231021#####'),
            'codigo_cabys' => null,
            'codigo_comercial' => null,
            'detalle' => $this->faker->sentence(3),
            'numero_vin_serie' => null,
            'registro_medicamento' => null,
            'forma_farmaceutica' => null,
            'cantidad' => $cantidad,
            'unidad_medida' => 'Sp',
            'unidad_medida_comercial' => null,
            'tipo_transaccion' => null,
            'precio_unitario' => $precioUnitario,
            'monto_total' => $montoTotal,
            'monto_descuento' => $descuento,
            'codigo_descuento' => null,
            'codigo_descuento_otro' => null,
            'naturaleza_descuento' => 'Descuento comercial',
            'subtotal' => $subtotal,
            'base_imponible' => $subtotal,
            'impuesto_codigo' => '01',
            'impuesto_codigo_tarifa' => '08',
            'impuesto_tarifa' => 13.00,
            'impuesto_monto' => round($impuesto, 5),
            'impuesto_neto' => round($impuesto, 5),
            'factor_calculo_iva' => null,
            'iva_cobrado_fabrica' => null,
            'impuesto_asumido_emisor_fabrica' => 0.00000,
            'monto_exportacion' => null,
            'exoneracion_tipo_documento' => null,
            'exoneracion_tipo_documento_otro' => null,
            'exoneracion_numero_documento' => null,
            'exoneracion_articulo' => null,
            'exoneracion_inciso' => null,
            'exoneracion_nombre_institucion' => null,
            'exoneracion_nombre_institucion_otros' => null,
            'exoneracion_fecha_emision' => null,
            'exoneracion_porcentaje' => null,
            'exoneracion_tarifa_exonerada' => null,
            'exoneracion_monto' => 0.00000,
            'monto_total_linea' => $montoTotalLinea,
            'metadata' => null,
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
