<?php

namespace Database\Factories;

use App\Models\BusUnidad;
use App\Models\Empresa;
use App\Models\ModeloBus;
use Illuminate\Database\Eloquent\Factories\Factory;

class BusUnidadFactory extends Factory
{
    protected $model = BusUnidad::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'placa' => strtoupper($this->faker->unique()->bothify('???-####')),
            'modelo_id' => ModeloBus::factory(),
            'capacidad_asientos' => $this->faker->numberBetween(20, 60),
            'identificador_interno' => $this->faker->optional()->numerify('FL-###'),
            'activo' => true,
            'eliminado' => false,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => false,
        ]);
    }
}
