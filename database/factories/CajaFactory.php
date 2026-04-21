<?php

namespace Database\Factories;

use App\Models\Caja;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class CajaFactory extends Factory
{
    protected $model = Caja::class;

    public function definition(): array
    {
        return [
            'sucursal_id' => Sucursal::factory(),
            'usuario_id' => Usuario::factory(),
            'nombre' => 'Caja ' . $this->faker->numberBetween(1, 20),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
