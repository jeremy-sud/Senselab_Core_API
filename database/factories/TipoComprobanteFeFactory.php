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
            'codigo_dgt' => $this->faker->unique()->numerify('##'),
            'nombre' => $this->faker->unique()->words(3, true),
            'descripcion' => $this->faker->optional()->sentence(),
            'requiere_referencia' => false,
            'permite_exportacion' => false,
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
