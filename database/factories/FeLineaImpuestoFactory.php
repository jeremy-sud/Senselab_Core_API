<?php

namespace Database\Factories;

use App\Models\FeLineaDetalle;
use App\Models\FeLineaImpuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeLineaImpuestoFactory extends Factory
{
    protected $model = FeLineaImpuesto::class;

    public function definition(): array
    {
        $tarifa = $this->faker->randomElement([1.00, 2.00, 4.00, 8.00, 13.00]);
        $baseImponible = $this->faker->randomFloat(5, 1000, 50000);
        $monto = round($baseImponible * $tarifa / 100, 5);

        return [
            'linea_detalle_id' => FeLineaDetalle::factory(),
            'codigo' => '01',
            'codigo_impuesto_otro' => null,
            'codigo_tarifa' => null,
            'codigo_tarifa_iva' => '08',
            'tarifa' => $tarifa,
            'factor_calculo_iva' => null,
            'monto' => $monto,
            'impuesto_asumido_emisor_fabrica' => null,
            'monto_exportacion' => null,
            'cantidad_unidad_medida' => null,
            'porcentaje' => null,
            'proporcion' => null,
            'volumen_unidad_consumo' => null,
            'impuesto_unidad' => null,
            'dato_especifico_codigo' => null,
            'dato_especifico_tipo_gravamen' => null,
            'dato_especifico_unidad_medida' => null,
            'dato_especifico_cantidad_base' => null,
            'dato_especifico_monto_gravamen' => null,
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
            'exoneracion_monto' => null,
        ];
    }

    public function iva13(): static
    {
        return $this->state(fn () => [
            'codigo' => '01',
            'codigo_tarifa_iva' => '08',
            'tarifa' => 13.00,
        ]);
    }

    public function selectivoConsumo(): static
    {
        return $this->state(fn () => [
            'codigo' => '02',
            'codigo_tarifa_iva' => null,
            'tarifa' => null,
        ]);
    }

    public function conExoneracion(): static
    {
        return $this->state(fn (array $attributes) => [
            'exoneracion_tipo_documento' => '01',
            'exoneracion_numero_documento' => $this->faker->numerify('EXO-########'),
            'exoneracion_nombre_institucion' => $this->faker->company(),
            'exoneracion_fecha_emision' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'exoneracion_tarifa_exonerada' => 13.00,
            'exoneracion_monto' => round((float) $attributes['monto'] * 0.5, 5),
        ]);
    }

    public function impuestoEspecifico(string $codigo = '04'): static
    {
        return $this->state(fn () => [
            'codigo' => $codigo,
            'codigo_tarifa_iva' => null,
            'tarifa' => null,
            'cantidad_unidad_medida' => $this->faker->randomFloat(2, 1, 100),
            'porcentaje' => $this->faker->randomFloat(2, 0.5, 25),
            'impuesto_unidad' => $this->faker->randomFloat(5, 10, 5000),
        ]);
    }
}
