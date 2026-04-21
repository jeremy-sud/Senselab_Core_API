<?php

namespace Database\Factories;

use App\Models\Presupuesto;
use Illuminate\Database\Eloquent\Factories\Factory;

class PresupuestoFactory extends Factory
{
    protected $model = Presupuesto::class;

    public function definition(): array
    {
        $inicio = $this->faker->dateTimeBetween('-3 months', 'now');

        return [
            'empresa_id' => \App\Models\Empresa::factory(),
            'nombre' => 'Presupuesto ' . implode(' ', $this->faker->words(2)),
            'periodo_inicio' => $inicio,
            'periodo_fin' => $this->faker->dateTimeBetween($inicio, '+6 months'),
            'estado' => $this->faker->randomElement(['Borrador', 'Activo', 'Finalizado']),
            'activo' => true,
        ];
    }
}
