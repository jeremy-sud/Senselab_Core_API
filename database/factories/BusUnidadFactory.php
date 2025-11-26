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
            'modelo_bus_id' => ModeloBus::factory(),
            'placa' => strtoupper($this->faker->unique()->bothify('???-####')),
            'numero_unidad' => $this->faker->unique()->numberBetween(1, 500),
            'capacidad_pasajeros' => $this->faker->numberBetween(20, 60),
            'year_fabricacion' => $this->faker->numberBetween(2010, 2024),
            'color' => $this->faker->safeColorName(),
            'numero_chasis' => strtoupper($this->faker->unique()->bothify('??########')),
            'numero_motor' => strtoupper($this->faker->unique()->bothify('??########')),
            'estado' => $this->faker->randomElement(['disponible', 'en_ruta', 'mantenimiento', 'fuera_servicio']),
            'kilometraje' => $this->faker->numberBetween(10000, 500000),
            'ultima_revision' => $this->faker->optional()->dateTimeBetween('-6 months', 'now'),
            'proxima_revision' => $this->faker->optional()->dateTimeBetween('now', '+6 months'),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }

    public function disponible(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'disponible',
        ]);
    }

    public function enRuta(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'en_ruta',
        ]);
    }
}
