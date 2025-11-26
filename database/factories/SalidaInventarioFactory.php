<?php

namespace Database\Factories;

use App\Models\SalidaInventario;
use App\Models\Almacen;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalidaInventarioFactory extends Factory
{
    protected $model = SalidaInventario::class;

    public function definition(): array
    {
        return [
            'almacen_id' => Almacen::factory(),
            'tipo_salida' => $this->faker->randomElement(['venta', 'devolucion', 'ajuste', 'traslado', 'merma']),
            'numero_documento' => $this->faker->unique()->numerify('SAL-####'),
            'fecha' => $this->faker->dateTimeThisMonth(),
            'responsable_id' => Usuario::factory(),
            'observaciones' => $this->faker->optional()->sentence(),
            'estado' => $this->faker->randomElement(['borrador', 'procesada', 'cancelada']),
        ];
    }
}
