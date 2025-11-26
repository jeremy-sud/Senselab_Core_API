<?php

namespace Database\Factories;

use App\Models\ZonaGeografica;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZonaGeograficaFactory extends Factory
{
    protected $model = ZonaGeografica::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('Z-###'),
            'nombre' => $this->faker->city(),
            'descripcion' => $this->faker->optional()->sentence(),
            'provincia' => $this->faker->randomElement(['San José', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón']),
            'canton' => $this->faker->optional()->city(),
            'distrito' => $this->faker->optional()->streetName(),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
