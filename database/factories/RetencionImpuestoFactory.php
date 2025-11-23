<?php

namespace Database\Factories;

use App\Models\RetencionImpuesto;
use App\Models\Empresa;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class RetencionImpuestoFactory extends Factory
{
    protected $model = RetencionImpuesto::class;

    public function definition(): array
    {
        $periodo = $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m');
        $montoBase = $this->faker->randomFloat(2, 50000, 5000000);
        $porcentaje = $this->faker->randomElement([1.0, 2.0, 10.0, 15.0]);
        $montoRetenido = $montoBase * ($porcentaje / 100);

        return [
            'empresa_id' => Empresa::factory(),
            'proveedor_id' => Proveedor::factory(),
            'compra_id' => null,
            'venta_id' => null,
            'tipo_retencion' => $this->faker->randomElement(['renta', 'iva', 'otras']),
            'porcentaje_retencion' => $porcentaje,
            'monto_base' => $montoBase,
            'monto_retenido' => $montoRetenido,
            'numero_comprobante' => $this->faker->optional()->numerify('RET-########'),
            'fecha_retencion' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'periodo_declaracion' => $periodo,
            'declarado' => $this->faker->boolean(30),
            'notas' => $this->faker->optional()->sentence(),
            'eliminado' => false,
        ];
    }
}
