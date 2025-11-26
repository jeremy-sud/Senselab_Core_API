<?php

namespace Database\Factories;

use App\Models\CuentaPorCobrar;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

class CuentaPorCobrarFactory extends Factory
{
    protected $model = CuentaPorCobrar::class;

    public function definition(): array
    {
        $fechaEmision = $this->faker->dateTimeBetween('-6 months', 'now');
        $diasCredito = $this->faker->numberBetween(15, 90);
        $fechaVencimiento = (clone $fechaEmision)->modify("+{$diasCredito} days");
        $montoTotal = $this->faker->randomFloat(2, 10000, 500000);
        $montoPagado = $this->faker->randomFloat(2, 0, $montoTotal);
        
        return [
            'empresa_id' => Empresa::factory(),
            'cliente_id' => Cliente::factory(),
            'venta_id' => Venta::factory(),
            'numero_documento' => $this->faker->unique()->numerify('CC-######'),
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
