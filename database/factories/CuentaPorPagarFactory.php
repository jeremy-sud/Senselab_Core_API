<?php

namespace Database\Factories;

use App\Models\CuentaPorPagar;
use App\Models\Proveedor;
use App\Models\OrdenCompra;
use Illuminate\Database\Eloquent\Factories\Factory;

class CuentaPorPagarFactory extends Factory
{
    protected $model = CuentaPorPagar::class;

    public function definition(): array
    {
        return [
            'proveedor_id' => Proveedor::factory(),
            'orden_compra_id' => OrdenCompra::factory(),
            'numero_documento' => $this->faker->unique()->numerify('CXP-######'),
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
