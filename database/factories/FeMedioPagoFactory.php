<?php

namespace Database\Factories;

use App\Models\ComprobanteElectronicoFe;
use App\Models\FeMedioPago;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeMedioPagoFactory extends Factory
{
    protected $model = FeMedioPago::class;

    public function definition(): array
    {
        return [
            'comprobante_id' => ComprobanteElectronicoFe::factory(),
            'tipo_medio_pago' => $this->faker->randomElement(['01', '02', '03', '04']),
            'total_medio_pago' => $this->faker->randomFloat(5, 1000, 100000),
        ];
    }

    public function efectivo(float $monto = 10000): static
    {
        return $this->state(fn () => [
            'tipo_medio_pago' => '01',
            'total_medio_pago' => $monto,
        ]);
    }

    public function tarjeta(float $monto = 10000): static
    {
        return $this->state(fn () => [
            'tipo_medio_pago' => '02',
            'total_medio_pago' => $monto,
        ]);
    }

    public function transferencia(float $monto = 10000): static
    {
        return $this->state(fn () => [
            'tipo_medio_pago' => '03',
            'total_medio_pago' => $monto,
        ]);
    }

    public function otros(string $descripcion = 'Criptomoneda'): static
    {
        return $this->state(fn () => [
            'tipo_medio_pago' => '99',
            'medio_pago_otros' => $descripcion,
        ]);
    }
}
