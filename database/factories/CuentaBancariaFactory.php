<?php

namespace Database\Factories;

use App\Models\CuentaBancaria;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class CuentaBancariaFactory extends Factory
{
    protected $model = CuentaBancaria::class;

    public function definition(): array
    {
        $bancos = [
            'Banco Nacional de Costa Rica',
            'Banco de Costa Rica',
            'BAC San José',
            'Banco Popular y de Desarrollo Comunal',
            'Scotiabank Costa Rica',
        ];

        return [
            'empresa_id' => Empresa::factory(),
            'banco' => $this->faker->randomElement($bancos),
            'numero_cuenta' => $this->faker->numerify('###-##-###-######-#'),
            'iban' => 'CR' . $this->faker->numerify('####################'),
            'tipo_cuenta' => $this->faker->randomElement(['corriente', 'ahorro', 'cliente']),
            'moneda' => $this->faker->randomElement(['CRC', 'USD']),
            'titular' => $this->faker->optional()->company(),
            'saldo_inicial' => $this->faker->randomFloat(2, 0, 1000000),
            'saldo_actual' => $this->faker->randomFloat(2, 0, 1000000),
            'activa' => $this->faker->boolean(90),
        ];
    }
}
