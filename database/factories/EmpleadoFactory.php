<?php

namespace Database\Factories;

use App\Models\Empleado;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmpleadoFactory extends Factory
{
    protected $model = Empleado::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'usuario_id' => null,
            'nombre' => $this->faker->firstName(),
            'primer_apellido' => $this->faker->lastName(),
            'segundo_apellido' => $this->faker->lastName(),
            'tipo_documento' => $this->faker->randomElement(['01', '02', '03']),
            'numero_documento' => $this->faker->unique()->numerify('#########'),
            'fecha_nacimiento' => $this->faker->dateTimeBetween('-60 years', '-18 years'),
            'fecha_ingreso' => $this->faker->dateTimeBetween('-5 years', 'now'),
            'cargo_id' => null,
            'departamento_id' => null,
            'salario' => $this->faker->randomFloat(2, 350000, 3000000),
            'direccion' => $this->faker->address(),
            'telefono' => $this->faker->numerify('########'),
            'email' => $this->faker->unique()->safeEmail(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
