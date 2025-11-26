<?php

namespace Database\Factories;

use App\Models\CuentaContable;
use App\Models\Empresa;
use App\Models\TipoCuenta;
use Illuminate\Database\Eloquent\Factories\Factory;

class CuentaContableFactory extends Factory
{
    protected $model = CuentaContable::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'tipo_cuenta_id' => TipoCuenta::factory(),
            'cuenta_padre_id' => null,
            'codigo' => $this->faker->unique()->numerify('####-##-##'),
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->optional()->sentence(),
            'nivel' => $this->faker->numberBetween(1, 5),
            'naturaleza' => $this->faker->randomElement(['deudora', 'acreedora']),
            'acepta_movimientos' => $this->faker->boolean(80),
            'saldo_actual' => $this->faker->randomFloat(2, -100000, 100000),
            'activo' => $this->faker->boolean(95),
        ];
    }

    public function deudora(): static
    {
        return $this->state(fn (array $attributes) => [
            'naturaleza' => 'deudora',
        ]);
    }

    public function acreedora(): static
    {
        return $this->state(fn (array $attributes) => [
            'naturaleza' => 'acreedora',
        ]);
    }
}
