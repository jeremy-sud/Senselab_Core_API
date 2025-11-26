<?php

namespace Database\Factories;

use App\Models\TipoComprobanteFe;
use Illuminate\Database\Eloquent\Factories\Factory;

class TipoComprobanteFeFactory extends Factory
{
    protected $model = TipoComprobanteFe::class;

    public function definition(): array
    {
        return [
            'codigo' => $this->faker->unique()->numerify('##'),
            'nombre' => $this->faker->randomElement(['Factura Electrónica', 'Nota de Crédito', 'Nota de Débito', 'Tiquete Electrónico']),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
