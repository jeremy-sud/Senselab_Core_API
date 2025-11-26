<?php

namespace Database\Factories;

use App\Models\ConsecutivoFe;
use App\Models\Empresa;
use App\Models\Sucursal;
use App\Models\TipoComprobanteFe;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsecutivoFeFactory extends Factory
{
    protected $model = ConsecutivoFe::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'sucursal_id' => Sucursal::factory(),
            'tipo_comprobante_id' => TipoComprobanteFe::factory(),
            'tipo_documento_dgt' => $this->faker->numberBetween(1, 9),
            'consecutivo_actual' => $consecutivo = $this->faker->numberBetween(1, 1000),
            'consecutivo_minimo' => 1,
            'consecutivo_maximo' => $this->faker->numberBetween($consecutivo + 1000, 99999),
            'situacion' => $this->faker->randomElement(['normal', 'sin_internet']),
            'estado' => 'activo',
            'ambiente' => $this->faker->randomElement(['stag', 'produccion']),
        ];
    }

    public function activo(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'activo',
        ]);
    }
}
