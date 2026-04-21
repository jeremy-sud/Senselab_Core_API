<?php

namespace Database\Factories;

use App\Models\MovimientoCajaChica;
use App\Models\CajaChica;
use App\Models\CuentaContable;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovimientoCajaChicaFactory extends Factory
{
    protected $model = MovimientoCajaChica::class;

    public function definition(): array
    {
        return [
            'caja_chica_id' => CajaChica::factory(),
            'fecha_movimiento' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'tipo_movimiento' => $this->faker->randomElement(['Ingreso', 'Egreso', 'Reembolso']),
            'monto' => $this->faker->randomFloat(2, 1000, 50000),
            'numero_comprobante' => $this->faker->optional()->numerify('COMP-####'),
            'concepto' => $this->faker->sentence(),
            'cuenta_contable_id' => null,
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
