<?php

namespace Database\Factories;

use App\Models\MensajeHacienda;
use App\Models\ComprobanteElectronicoFe;
use Illuminate\Database\Eloquent\Factories\Factory;

class MensajeHaciendaFactory extends Factory
{
    protected $model = MensajeHacienda::class;

    public function definition(): array
    {
        return [
            'comprobante_electronico_id' => ComprobanteElectronicoFe::factory(),
            'tipo_mensaje' => $this->faker->randomElement(['aceptacion', 'rechazo', 'confirmacion']),
            'clave' => $this->faker->numerify('##################################################'),
            'fecha_emision' => $this->faker->dateTimeThisMonth(),
            'mensaje' => $this->faker->randomElement(['Aceptado', 'Rechazado', 'Confirmado']),
            'detalle_mensaje' => $this->faker->optional()->sentence(),
            'codigo_actividad' => $this->faker->optional()->numerify('######'),
            'xml_mensaje' => $this->faker->optional()->text(200),
            'estado' => $this->faker->randomElement(['enviado', 'procesado', 'error']),
        ];
    }
}
