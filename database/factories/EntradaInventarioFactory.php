<?php

namespace Database\Factories;

use App\Models\EntradaInventario;
use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\Proveedor;
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
            'tipo_entrada' => $this->faker->randomElement(['Compra', 'Devolucion', 'Ajuste', 'Traslado']),
            'fecha_entrada' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'documento_referencia' => $this->faker->optional()->numerify('DOC-######'),
            'monto_total' => $this->faker->randomFloat(2, 10000, 500000),
            'estado' => $this->faker->randomElement(['Pendiente', 'Procesada', 'Cancelada']),
            'observaciones' => $this->faker->optional()->sentence(),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => true,
        ];
    }

    public function procesada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'procesada',
        ]);
    }
}
