<?php

namespace Database\Factories;

use App\Models\ZonaGeografica;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZonaGeograficaFactory extends Factory
{
    protected $model = ZonaGeografica::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'codigo' => $this->faker->unique()->numerify('ZG-###'),
            'nombre' => $this->faker->unique()->city(),
            'tipo' => $this->faker->randomElement(['provincia', 'canton', 'distrito', 'zona_ventas']),
            'zona_padre_id' => null,
            'provincias_incluidas' => $this->faker->optional()->randomElement(['San José', 'Alajuela', 'Cartago']),
            'vendedor_asignado_id' => null,
            'activa' => true,
            'eliminado' => false,
        ];
    }
}
