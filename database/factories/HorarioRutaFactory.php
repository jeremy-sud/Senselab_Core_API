<?php

namespace Database\Factories;

use App\Models\HorarioRuta;
use App\Models\Ruta;
use App\Models\BusUnidad;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class HorarioRutaFactory extends Factory
{
    protected $model = HorarioRuta::class;

    public function definition(): array
    {
        $horaInicio = $this->faker->time('H:i');
        $horaFin = $this->faker->time('H:i');
        
        return [
            'ruta_id' => Ruta::factory(),
            'bus_unidad_id' => BusUnidad::factory(),
            'conductor_id' => Usuario::factory(),
            'fecha' => $this->faker->dateTimeBetween('now', '+1 month'),
            'hora_salida' => $horaInicio,
            'hora_llegada_estimada' => $horaFin,
            'hora_salida_real' => null,
            'hora_llegada_real' => null,
            'asientos_disponibles' => $this->faker->numberBetween(20, 50),
            'asientos_ocupados' => $this->faker->numberBetween(0, 20),
            'precio_base' => $this->faker->randomFloat(2, 1000, 5000),
            'estado' => $this->faker->randomElement(['programado', 'en_curso', 'completado', 'cancelado']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }

    public function programado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'programado',
        ]);
    }
}
