<?php

namespace Database\Factories;

use App\Models\WebhookLog;
use App\Models\Webhook;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookLogFactory extends Factory
{
    protected $model = WebhookLog::class;

    public function definition(): array
    {
        return [
            'webhook_id' => Webhook::factory(),
            'empresa_id' => Empresa::factory(),
            'evento' => $this->faker->randomElement(Webhook::EVENTOS_DISPONIBLES),
            'estado' => $this->faker->randomElement([
                WebhookLog::ESTADO_PENDIENTE,
                WebhookLog::ESTADO_EXITOSO,
                WebhookLog::ESTADO_FALLIDO,
            ]),
            'codigo_respuesta' => $this->faker->randomElement([200, 201, 400, 500, null]),
            'latencia_ms' => $this->faker->numberBetween(50, 5000),
            'payload_size' => $this->faker->numberBetween(100, 10000),
            'payload' => ['evento' => 'test', 'datos' => ['id' => 1]],
            'respuesta' => $this->faker->optional()->sentence(),
            'error' => null,
            'intento' => 1,
            'proximo_reintento_en' => null,
        ];
    }

    public function exitoso(): static
    {
        return $this->state(fn () => [
            'estado' => WebhookLog::ESTADO_EXITOSO,
            'codigo_respuesta' => 200,
            'error' => null,
        ]);
    }

    public function fallido(): static
    {
        return $this->state(fn () => [
            'estado' => WebhookLog::ESTADO_FALLIDO,
            'codigo_respuesta' => 500,
            'error' => 'Internal Server Error',
        ]);
    }
}
