<?php

namespace Database\Factories;

use App\Models\MovimientoBancario;
use App\Models\CuentaBancaria;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovimientoBancarioFactory extends Factory
{
    protected $model = MovimientoBancario::class;

    public function definition(): array
    {
        $conciliado = $this->faker->boolean(70);
        $monto = $this->faker->randomFloat(2, 1000, 500000);

        return [
            'cuenta_bancaria_id' => CuentaBancaria::factory(),
            'empresa_id' => Empresa::factory(),
            'fecha_movimiento' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'fecha_valor' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
            'tipo_movimiento' => $this->faker->randomElement(['deposito', 'retiro', 'transferencia_entrada', 'transferencia_salida', 'comision', 'interes', 'ajuste']),
            'numero_referencia' => $this->faker->optional()->numerify('REF-####-####'),
            'descripcion' => $this->faker->sentence(),
            'monto' => $monto,
            'saldo_despues' => $this->faker->optional()->randomFloat(2, $monto, 1000000),
            'beneficiario' => $this->faker->optional()->name(),
            'conciliado' => $conciliado,
            'fecha_conciliacion' => $conciliado ? $this->faker->dateTimeBetween('-3 months', 'now') : null,
            'asiento_contable_id' => null,
            'notas' => $this->faker->optional()->sentence(),
            'eliminado' => false,
        ];
    }
}
