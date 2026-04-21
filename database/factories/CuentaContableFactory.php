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
            'nombre' => $this->faker->unique()->words(3, true),
            'descripcion' => $this->faker->optional()->sentence(),
            'codigo' => $this->faker->unique()->numerify('#.##.##.###'),
            'tipo_cuenta_id' => TipoCuenta::factory(),
            'cuenta_padre_id' => null,
            'permite_movimientos' => true,
            'saldo_actual' => 0,
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
