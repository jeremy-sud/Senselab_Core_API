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
            'tipo_identificacion' => $this->faker->randomElement(['01', '02', '03', '04']),
            'numero_identificacion' => $this->faker->unique()->numerify('#########'),
            'nombre' => $this->faker->firstName(),
            'apellidos' => $this->faker->lastName() . ' ' . $this->faker->lastName(),
            'nombre_comercial' => $this->faker->optional()->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'telefono' => $this->faker->numerify('########'),
            'direccion' => $this->faker->address(),
            'provincia' => $this->faker->randomElement(['San José', 'Alajuela', 'Cartago', 'Heredia']),
            'canton' => $this->faker->city(),
            'distrito' => $this->faker->citySuffix(),
            'limite_credito' => $this->faker->randomFloat(2, 0, 500000),
            'plazo_credito_dias' => $this->faker->randomElement([0, 15, 30, 60, 90]),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
