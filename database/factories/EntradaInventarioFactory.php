<?php

namespace Database\Factories;

use App\Models\EntradaInventario;
use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class EntradaInventarioFactory extends Factory
{
    protected $model = EntradaInventario::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'almacen_id' => Almacen::factory(),
            'proveedor_id' => Proveedor::factory(),
            'usuario_id' => Usuario::factory(),
            'numero_entrada' => $this->faker->unique()->numerify('ENT-######'),
            'tipo_entrada' => $this->faker->randomElement(['compra', 'devolucion', 'ajuste', 'traslado']),
            'fecha_entrada' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'numero_factura' => $this->faker->optional()->numerify('FAC-######'),
            'total' => $this->faker->randomFloat(2, 10000, 500000),
            'estado' => $this->faker->randomElement(['borrador', 'procesada', 'cancelada']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }

    public function procesada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'procesada',
        ]);
    }
}
