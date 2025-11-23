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
            'tipo_cuenta' => $this->faker->randomElement(['corriente', 'ahorros', 'cliente', 'colones', 'dolares']),
            'moneda' => $this->faker->randomElement(['CRC', 'USD', 'EUR']),
            'saldo_actual' => $this->faker->randomFloat(2, 0, 1000000),
            'cuenta_contable_id' => null,
            'sucursal_banco' => $this->faker->optional()->city(),
            'contacto_ejecutivo' => $this->faker->optional()->name(),
            'telefono_ejecutivo' => $this->faker->optional()->numerify('####-####'),
            'activa' => $this->faker->boolean(90),
            'es_principal' => false,
            'notas' => $this->faker->optional()->sentence(),
            'eliminado' => false,
        ];
    }
}
