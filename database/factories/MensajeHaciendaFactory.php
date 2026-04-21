<?php

namespace Database\Factories;

use App\Models\MensajeHacienda;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class MensajeHaciendaFactory extends Factory
{
    protected $model = MensajeHacienda::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'comprobante_id' => null,
            'clave_numerica' => $this->faker->numerify(str_repeat('#', 50)),
            'tipo_mensaje' => $this->faker->randomElement(['confirmacion', 'rechazo', 'aceptacion_parcial']),
            'codigo_respuesta' => $this->faker->randomElement(['1', '2', '3']),
            'detalle_mensaje' => $this->faker->optional()->sentence(),
            'xml_respuesta' => '<xml>respuesta</xml>',
            'fecha_emision' => now(),
            'fecha_procesamiento' => now(),
            'estado' => $this->faker->randomElement(['pendiente', 'procesado', 'error']),
            'intentos_envio' => 0,
            'ultimo_error' => null,
            'eliminado' => false,
        ];
    }
}
