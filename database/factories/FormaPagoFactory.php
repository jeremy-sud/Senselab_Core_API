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
            'codigo_dgt' => $this->faker->unique()->numerify('##'),
            'nombre' => $this->faker->unique()->words(2, true),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
