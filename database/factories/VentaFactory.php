<?php

namespace Database\Factories;

use App\Models\Venta;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\FormaPago;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    protected $model = Venta::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 10000, 500000);
        $descuento = $this->faker->randomFloat(2, 0, 10000);
        $subtotalNeto = $subtotal - $descuento;
        $impuesto = round($subtotalNeto * 0.13, 2);
        $total = $subtotalNeto + $impuesto;

        return [
            'empresa_id' => Empresa::factory(),
            'sucursal_id' => Sucursal::factory(),
            'cliente_id' => Cliente::factory(),
            'usuario_id' => Usuario::factory(),
            'fecha_venta' => $this->faker->dateTimeThisMonth(),
            'moneda' => 'CRC',
            'subtotal_bruto_total' => $subtotal,
            'monto_descuento_total' => $descuento,
            'subtotal_neto_total' => $subtotalNeto,
            'monto_impuesto_total' => $impuesto,
            'monto_total_venta' => $total,
            'estado_venta' => $this->faker->randomElement(['pendiente', 'completada', 'cancelada']),
            'estado_hacienda' => 'Pendiente',
            'forma_pago_id' => FormaPago::factory(),
            'observaciones' => $this->faker->optional()->sentence(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
