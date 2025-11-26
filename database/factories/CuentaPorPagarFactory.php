<?php

namespace Database\Factories;

use App\Models\CuentaPorPagar;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\OrdenCompra;
use Illuminate\Database\Eloquent\Factories\Factory;

class CuentaPorPagarFactory extends Factory
{
    protected $model = CuentaPorPagar::class;

    public function definition(): array
    {
        $fechaEmision = $this->faker->dateTimeBetween('-6 months', 'now');
        $diasCredito = $this->faker->numberBetween(15, 90);
        $fechaVencimiento = (clone $fechaEmision)->modify("+{$diasCredito} days");
        $montoTotal = $this->faker->randomFloat(2, 10000, 500000);
        $montoPagado = $this->faker->randomFloat(2, 0, $montoTotal);
        
        return [
            'empresa_id' => Empresa::factory(),
            'proveedor_id' => Proveedor::factory(),
            'orden_compra_id' => OrdenCompra::factory(),
            'numero_documento' => $this->faker->unique()->numerify('CP-######'),
            'tipo_documento' => $this->faker->randomElement(['factura', 'nota_debito']),
            'fecha_emision' => $fechaEmision,
            'fecha_vencimiento' => $fechaVencimiento,
            'dias_credito' => $diasCredito,
            'monto_total' => $montoTotal,
            'monto_pagado' => $montoPagado,
            'saldo_pendiente' => $montoTotal - $montoPagado,
            'estado' => $this->faker->randomElement(['pendiente', 'parcial', 'pagada', 'vencida']),
            'moneda' => 'CRC',
            'tipo_cambio' => 1,
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }

    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'monto_pagado' => 0,
            'saldo_pendiente' => $attributes['monto_total'],
            'estado' => 'pendiente',
        ]);
    }

    public function pagada(): static
    {
        return $this->state(fn (array $attributes) => [
            'monto_pagado' => $attributes['monto_total'],
            'saldo_pendiente' => 0,
            'estado' => 'pagada',
        ]);
    }
}
