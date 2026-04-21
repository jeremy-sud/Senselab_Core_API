<?php

namespace Database\Factories;

use App\Models\HorarioRuta;
use App\Models\Ruta;
use App\Models\BusUnidad;
use Illuminate\Database\Eloquent\Factories\Factory;

class HorarioRutaFactory extends Factory
{
    protected $model = HorarioRuta::class;

    public function definition(): array
    {
        return [
            'ruta_id' => Ruta::factory(),
            'bus_id' => BusUnidad::factory(),
            'fecha_salida' => $this->faker->dateTimeBetween('now', '+30 days'),
            'hora_salida' => $this->faker->time('H:i'),
            'fecha_llegada_estimada' => $this->faker->dateTimeBetween('+1 day', '+31 days'),
            'hora_llegada_estimada' => $this->faker->time('H:i'),
            'asientos_disponibles' => $this->faker->numberBetween(10, 50),
            'estado' => 'Programado',
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
