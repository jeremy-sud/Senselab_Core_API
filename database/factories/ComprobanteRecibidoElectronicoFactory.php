<?php

namespace Database\Factories;

use App\Models\ComprobanteRecibidoElectronico;
use App\Models\Empresa;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComprobanteRecibidoElectronicoFactory extends Factory
{
    protected $model = ComprobanteRecibidoElectronico::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'proveedor_id' => Proveedor::factory(),
            'clave_numerica' => $this->faker->numerify(str_repeat('#', 50)),
            'consecutivo' => $this->faker->numerify('##########'),
            'fecha_emision' => $this->faker->dateTimeBetween('-3 months', 'now'),
            'tipo_documento' => $this->faker->randomElement(['01', '02', '03', '04']),
            'numero_cedula_emisor' => $this->faker->numerify('#########'),
            'nombre_emisor' => $this->faker->company(),
            'monto_total' => $this->faker->randomFloat(2, 1000, 500000),
            'monto_impuesto' => $this->faker->randomFloat(2, 0, 50000),
            'moneda' => 'CRC',
            'xml_original' => '<xml>comprobante</xml>',
            'estado_validacion' => $this->faker->randomElement(['pendiente', 'aceptado', 'rechazado']),
            'mensaje_hacienda' => null,
            'detalle_mensaje' => null,
            'contabilizado' => false,
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
