<?php

namespace Database\Factories;

use App\Models\Presupuesto;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class PresupuestoFactory extends Factory
{
    protected $model = Presupuesto::class;

    public function definition(): array
    {
        return [
            'numero_presupuesto' => $this->faker->unique()->numerify('PRES-####'),
            'cliente_id' => \App\Models\Cliente::factory(),
            'usuario_id' => Usuario::factory(),
            'fecha_emision' => $this->faker->dateTimeThisMonth(),
            'fecha_vencimiento' => $this->faker->dateTimeBetween('now', '+1 month'),
            'subtotal' => $this->faker->randomFloat(2, 10000, 500000),
            'impuesto' => $this->faker->randomFloat(2, 1300, 65000),
            'total' => $this->faker->randomFloat(2, 11300, 565000),
            'estado' => $this->faker->randomElement(['borrador', 'enviado', 'aceptado', 'rechazado', 'vencido']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
