<?php

namespace Database\Factories;

use App\Models\EntidadEtiqueta;
use App\Models\Etiqueta;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntidadEtiquetaFactory extends Factory
{
    protected $model = EntidadEtiqueta::class;

    public function definition(): array
    {
        return [
            'etiqueta_id' => Etiqueta::factory(),
            'entidad_tipo' => $this->faker->randomElement(['App\\Models\\Cliente', 'App\\Models\\Producto', 'App\\Models\\Venta']),
            'entidad_id' => $this->faker->numberBetween(1, 100),
        ];
    }
}
