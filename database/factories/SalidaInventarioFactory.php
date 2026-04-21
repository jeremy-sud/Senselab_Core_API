<?php

namespace Database\Factories;

use App\Models\SalidaInventario;
use App\Models\Empresa;
use App\Models\Almacen;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalidaInventarioFactory extends Factory
{
    protected $model = SalidaInventario::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'almacen_id' => Almacen::factory(),
            'fecha_salida' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'tipo_salida' => $this->faker->randomElement(['venta', 'devolucion', 'ajuste', 'merma']),
            'venta_id' => null,
            'cliente_id' => null,
            'proveedor_id' => null,
            'documento_referencia' => $this->faker->optional()->numerify('SAL-######'),
            'estado' => $this->faker->randomElement(['borrador', 'confirmada', 'anulada']),
            'monto_total' => $this->faker->randomFloat(2, 1000, 500000),
            'observaciones' => $this->faker->optional()->sentence(),
            'descripcion' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
