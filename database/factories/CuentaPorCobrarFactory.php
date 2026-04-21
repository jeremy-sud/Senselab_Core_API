<?php

namespace Database\Factories;

use App\Models\CuentaPorCobrar;
use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

class CuentaPorCobrarFactory extends Factory
{
    protected $model = CuentaPorCobrar::class;

    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'venta_id' => Venta::factory(),
            'numero_documento' => $this->faker->unique()->numerify('CXC-######'),
            'fecha_emision' => $fecha = $this->faker->dateTimeBetween('-3 months', 'now'),
            'fecha_vencimiento' => $this->faker->dateTimeBetween($fecha, '+3 months'),
            'moneda' => 'CRC',
            'monto_original' => $monto = $this->faker->randomFloat(2, 5000, 500000),
            'monto_pagado' => 0,
            'monto_pendiente' => $monto,
            'estado' => 'pendiente',
            'observaciones' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pendiente',
        ]);
    }

    public function pagada(): static
    {
        return $this->state(fn (array $attributes) => [
            'monto_pagado' => $attributes['monto_original'],
            'monto_pendiente' => 0,
            'estado' => 'pagada',
        ]);
    }
}
