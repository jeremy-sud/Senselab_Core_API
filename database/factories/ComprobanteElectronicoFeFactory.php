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
            'receptor_tipo_identificacion' => '01',
            'receptor_numero_identificacion' => $this->faker->numerify('#########'),
            'receptor_nombre' => $this->faker->company,
            'receptor_email' => $this->faker->email,
            'receptor_provincia' => null,
            'receptor_canton' => null,
            'receptor_distrito' => null,
            'receptor_barrio' => null,
            'receptor_otras_senas' => null,
            'receptor_otras_senas_extranjero' => null,
            'receptor_nombre_comercial' => null,
            'receptor_telefono_codigo_pais' => null,
            'receptor_telefono_numero' => null,
            'codigo_actividad_receptor' => null,
            'moneda' => 'CRC',
            'tipo_cambio' => 1.00000,
            'observaciones' => null,
            'total_servicios_gravados' => 0.00000,
            'total_servicios_exentos' => 0.00000,
            'total_servicios_exonerados' => 0.00000,
            'total_servicios_no_sujeto' => 0.00000,
            'total_mercancias_gravadas' => 0.00000,
            'total_mercancias_exentas' => 0.00000,
            'total_mercancias_exoneradas' => 0.00000,
            'total_mercancias_no_sujeta' => 0.00000,
            'total_gravado' => 0.00000,
            'total_exento' => 0.00000,
            'total_exonerado' => 0.00000,
            'total_no_sujeto' => 0.00000,
            'total_venta' => $this->faker->randomFloat(5, 1000, 100000),
            'total_descuentos' => 0.00000,
            'total_venta_neta' => $this->faker->randomFloat(5, 1000, 100000),
            'total_impuesto' => $this->faker->randomFloat(5, 100, 13000),
            'total_imp_asum_emisor_fabrica' => 0.00000,
            'total_iva_devuelto' => 0.00000,
            'total_otros_cargos' => 0.00000,
            'total_comprobante' => $this->faker->randomFloat(5, 1100, 113000),
            'condicion_venta' => $this->faker->randomElement(['01', '02', '03']),
            'condicion_venta_otros' => null,
            'medio_pago' => $this->faker->randomElement(['01', '02', '03', '04']),
            'plazo_credito' => null,
            'tipo_transaccion' => null,
            'emisor_otras_senas_extranjero' => null,
            'xml_original' => null,
            'xml_firmado' => null,
            'estado' => $this->faker->randomElement(['pendiente', 'enviando', 'recibido', 'aceptado', 'rechazado']),
            'situacion' => '1',
            'respuesta_hacienda_xml' => null,
            'mensaje_hacienda' => null,
            'codigo_respuesta_hacienda' => null,
            'fecha_envio' => null,
            'fecha_recibido' => null,
            'fecha_procesado' => null,
            'fecha_respuesta' => null,
            'intentos_envio' => 0,
            'ultimo_intento' => null,
            'ultimo_error' => null,
            'metadata' => null,
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
