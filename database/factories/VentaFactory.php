<?php

namespace Database\Factories;

use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    protected $model = Venta::class;

    public function definition(): array
    {
        return [
            'numero_venta' => $this->faker->unique()->numerify('V-########'),
            'cliente_id' => Cliente::factory(),
            'usuario_id' => Usuario::factory(),
            'fecha' => $this->faker->dateTimeThisMonth(),
            'subtotal' => $this->faker->randomFloat(2, 10000, 500000),
            'impuesto' => $this->faker->randomFloat(2, 1300, 65000),
            'descuento' => $this->faker->randomFloat(2, 0, 10000),
            'total' => $this->faker->randomFloat(2, 11300, 555000),
            'estado' => $this->faker->randomElement(['pendiente', 'completada', 'cancelada']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
