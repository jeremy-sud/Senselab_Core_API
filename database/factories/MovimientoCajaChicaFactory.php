<?php

namespace Database\Factories;

use App\Models\MovimientoCajaChica;
use App\Models\CajaChica;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovimientoCajaChicaFactory extends Factory
{
    protected $model = MovimientoCajaChica::class;

    public function definition(): array
    {
        return [
            'caja_chica_id' => CajaChica::factory(),
            'tipo_movimiento' => $this->faker->randomElement(['ingreso', 'egreso', 'apertura', 'cierre']),
            'monto' => $this->faker->randomFloat(2, 100, 50000),
            'descripcion' => $this->faker->sentence(),
            'categoria' => $this->faker->optional()->randomElement(['combustible', 'viaticos', 'materiales', 'servicios']),
            'responsable_id' => Usuario::factory(),
            'fecha' => $this->faker->dateTimeThisMonth(),
            'numero_comprobante' => $this->faker->optional()->numerify('COMP-####'),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
