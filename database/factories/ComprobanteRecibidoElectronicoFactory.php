<?php

namespace Database\Factories;

use App\Models\ComprobanteRecibidoElectronico;
use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComprobanteRecibidoElectronicoFactory extends Factory
{
    protected $model = ComprobanteRecibidoElectronico::class;

    public function definition(): array
    {
        return [
            'proveedor_id' => Proveedor::factory(),
            'clave' => $this->faker->numerify('##################################################'),
            'numero_consecutivo' => $this->faker->numerify('##########'),
            'fecha_emision' => $this->faker->dateTimeThisMonth(),
            'emisor_nombre' => $this->faker->company(),
            'emisor_identificacion' => $this->faker->numerify('##########'),
            'receptor_nombre' => $this->faker->company(),
            'receptor_identificacion' => $this->faker->numerify('##########'),
            'tipo_comprobante' => $this->faker->randomElement(['01', '02', '03', '04']),
            'monto_total' => $this->faker->randomFloat(2, 1000, 500000),
            'monto_impuesto' => $this->faker->randomFloat(2, 130, 65000),
            'xml_contenido' => $this->faker->optional()->text(500),
            'estado' => $this->faker->randomElement(['recibido', 'aceptado', 'rechazado']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
