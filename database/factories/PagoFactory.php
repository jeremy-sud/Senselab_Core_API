<?php

namespace Database\Factories;

use App\Models\Pago;
use App\Models\Empresa;
use App\Models\FormaPago;
use Illuminate\Database\Eloquent\Factories\Factory;

class PagoFactory extends Factory
{
    protected $model = Pago::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'orden_compra_id' => null,
            'cuenta_por_pagar_id' => null,
            'proveedor_id' => null,
            'cliente_id' => null,
            'cuenta_por_cobrar_id' => null,
            'forma_pago_id' => FormaPago::factory(),
            'fecha_pago' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'monto' => $this->faker->randomFloat(2, 1000, 500000),
            'moneda' => 'CRC',
            'descripcion' => $this->faker->optional()->sentence(),
            'referencia' => $this->faker->optional()->numerify('REF-########'),
            'estado' => $this->faker->randomElement(['pendiente', 'aplicado', 'anulado']),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
