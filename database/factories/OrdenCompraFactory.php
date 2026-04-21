<?php

namespace Database\Factories;

use App\Models\OrdenCompra;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrdenCompraFactory extends Factory
{
    protected $model = OrdenCompra::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 10000, 500000);
        $impuesto = $subtotal * 0.13;

        return [
            'empresa_id' => Empresa::factory(),
            'proveedor_id' => Proveedor::factory(),
            'usuario_id' => Usuario::factory(),
            'numero_orden' => $this->faker->unique()->numerify('OC-######'),
            'fecha_orden' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'fecha_entrega_esperada' => $this->faker->dateTimeBetween('now', '+3 months'),
            'moneda' => 'CRC',
            'subtotal' => $subtotal,
            'impuesto_total' => round($impuesto, 2),
            'total_orden' => round($subtotal + $impuesto, 2),
            'estado' => $this->faker->randomElement(['borrador', 'enviada', 'aprobada', 'recibida']),
            'observaciones' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
