<?php

namespace Database\Factories;

use App\Models\Empleado;
use App\Models\Cargo;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmpleadoFactory extends Factory
{
    protected $model = Empleado::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'cargo_id' => Cargo::factory(),
            'nombre' => $this->faker->firstName(),
            'apellido1' => $this->faker->lastName(),
            'apellido2' => $this->faker->lastName(),
            'identificacion' => $this->faker->unique()->numerify('#########'),
            'tipo_identificacion' => 'cedula_fisica',
            'email' => $this->faker->unique()->safeEmail(),
            'telefono' => $this->faker->numerify('####-####'),
            'direccion' => $this->faker->address(),
            'fecha_nacimiento' => $this->faker->date('Y-m-d', '-25 years'),
            'fecha_ingreso' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'salario' => $this->faker->randomFloat(2, 300000, 2000000),
            'estado' => $this->faker->randomElement(['activo', 'inactivo', 'vacaciones', 'incapacitado']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activo',
        ]);
    }
}
