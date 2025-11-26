<?php

namespace Database\Factories;

use App\Models\DeduccionLegal;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeduccionLegalFactory extends Factory
{
    protected $model = DeduccionLegal::class;

    public function definition(): array
    {
        return [
            'codigo' => strtoupper($this->faker->unique()->lexify('DED-???')),
            'nombre' => $this->faker->words(3, true),
            'descripcion' => $this->faker->optional()->sentence(),
            'tipo' => $this->faker->randomElement(['porcentaje', 'monto_fijo']),
            'valor' => $this->faker->randomFloat(2, 0, 10),
            'aplica_empleador' => $this->faker->boolean(50),
            'aplica_empleado' => $this->faker->boolean(80),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
