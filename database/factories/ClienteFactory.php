<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'tipo_documento_interno' => $this->faker->randomElement(['cedula_fisica', 'cedula_juridica', 'dimex', 'nite']),
            'num_identificacion_dgt' => $this->faker->unique()->numerify('#########'),
            'tipo_identificacion_dgt' => $this->faker->randomElement(['01', '02', '03', '04']),
            'telefono' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'actividad_economica_dgt' => $this->faker->numerify('######'),
            'direccion' => $this->faker->address(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
