<?php

namespace Database\Factories;

use App\Models\Ruta;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class RutaFactory extends Factory
{
    protected $model = Ruta::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => $this->faker->city() . ' - ' . $this->faker->city(),
            'origen' => $this->faker->city(),
            'destino' => $this->faker->city(),
            'distancia_km' => $this->faker->randomFloat(2, 5, 300),
            'duracion_estimada' => $this->faker->numberBetween(30, 480),
            'tarifa_base' => $this->faker->randomFloat(2, 500, 15000),
            'observaciones' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
