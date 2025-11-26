<?php

namespace Database\Factories;

use App\Models\FormaPago;
use Illuminate\Database\Eloquent\Factories\Factory;

class FormaPagoFactory extends Factory
{
    protected $model = FormaPago::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('##'),
            'nombre' => $this->faker->randomElement(['Efectivo', 'Tarjeta', 'Transferencia', 'Cheque', 'Crédito']),
            'descripcion' => $this->faker->optional()->sentence(),
            'requiere_referencia' => $this->faker->boolean(50),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
