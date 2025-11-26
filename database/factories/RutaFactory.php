<?php

namespace Database\Factories;

use App\Models\Ruta;
use App\Models\ZonaGeografica;
use Illuminate\Database\Eloquent\Factories\Factory;

class RutaFactory extends Factory
{
    protected $model = Ruta::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('R-###'),
            'nombre' => $this->faker->streetName(),
            'descripcion' => $this->faker->optional()->sentence(),
            'origen_zona_id' => ZonaGeografica::factory(),
            'destino_zona_id' => ZonaGeografica::factory(),
            'distancia_km' => $this->faker->randomFloat(2, 5, 200),
            'duracion_estimada_minutos' => $this->faker->numberBetween(30, 480),
            'precio_base' => $this->faker->randomFloat(2, 500, 10000),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
