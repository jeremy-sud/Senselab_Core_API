<?php

namespace Database\Factories;

use App\Models\Etiqueta;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class EtiquetaFactory extends Factory
{
    protected $model = Etiqueta::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => $this->faker->unique()->word(),
            'color_hex' => $this->faker->hexColor(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
