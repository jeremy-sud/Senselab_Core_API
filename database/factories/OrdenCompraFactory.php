<?php

namespace Database\Factories;

use App\Models\OrdenCompra;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdenCompraFactory extends Factory
{
    protected $model = OrdenCompra::class;

    public function definition(): array
    {
        return [
            'numero_orden' => $this->faker->unique()->numerify('OC-####'),
            'proveedor_id' => Proveedor::factory(),
            'usuario_id' => Usuario::factory(),
            'fecha_orden' => $this->faker->dateTimeThisMonth(),
            'fecha_entrega_estimada' => $this->faker->dateTimeBetween('now', '+1 month'),
            'fecha_entrega_real' => null,
            'subtotal' => $this->faker->randomFloat(2, 10000, 500000),
            'impuesto' => $this->faker->randomFloat(2, 1300, 65000),
            'total' => $this->faker->randomFloat(2, 11300, 565000),
            'estado' => $this->faker->randomElement(['borrador', 'enviada', 'recibida', 'cancelada']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
