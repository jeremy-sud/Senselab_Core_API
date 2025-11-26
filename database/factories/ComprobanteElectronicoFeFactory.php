<?php

namespace Database\Factories;

use App\Models\ComprobanteElectronicoFe;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComprobanteElectronicoFeFactory extends Factory
{
    protected $model = ComprobanteElectronicoFe::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'tipo_documento' => $this->faker->randomElement(['01', '02', '03', '04']),
            'clave' => '5' . str_pad($this->faker->numberBetween(1, 999999999), 49, '0', STR_PAD_LEFT),
            'consecutivo' => str_pad($this->faker->numberBetween(1, 999999), 20, '0', STR_PAD_LEFT),
            'fecha_emision' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'condicion_venta' => $this->faker->randomElement(['01', '02', '03']),
            'plazo_credito' => null,
            'medio_pago' => $this->faker->randomElement(['01', '02', '03', '04']),
            'situacion' => '1',
            
            'receptor_nombre' => $this->faker->company,
            'receptor_tipo_identificacion' => '01',
            'receptor_numero_identificacion' => $this->faker->numerify('#########'),
            'receptor_email' => $this->faker->email,
            
            'codigo_moneda' => 'CRC',
            'tipo_cambio' => 1.00000,
            
            'total_venta_bruta' => $this->faker->randomFloat(5, 1000, 100000),
            'total_descuentos' => 0.00000,
            'total_venta_neta' => $this->faker->randomFloat(5, 1000, 100000),
            'total_impuestos' => $this->faker->randomFloat(5, 100, 13000),
            'total_comprobante' => $this->faker->randomFloat(5, 1100, 113000),
            
            'estado' => $this->faker->randomElement(['pendiente', 'enviando', 'recibido', 'aceptado', 'rechazado']),
            'intentos_envio' => 0,
        ];
    }

    public function aceptado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'aceptado',
            'fecha_envio' => now(),
            'fecha_respuesta' => now(),
        ]);
    }

    public function pendiente(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'pendiente',
        ]);
    }

    public function conXml(): static
    {
        return $this->state(fn (array $attributes) => [
            'xml_original' => '<?xml version="1.0"?><Factura>test</Factura>',
            'xml_firmado' => '<?xml version="1.0"?><Factura><Signature>test</Signature></Factura>',
        ]);
    }
}
