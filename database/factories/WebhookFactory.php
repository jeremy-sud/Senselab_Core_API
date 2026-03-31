<?php

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => $this->faker->words(3, true) . ' webhook',
            'url' => 'https://' . $this->faker->domainName() . '/webhook',
            'eventos' => $this->faker->randomElements(
                Webhook::EVENTOS_DISPONIBLES,
                $this->faker->numberBetween(1, 3)
            ),
            'secret' => Str::random(64),
            'descripcion' => $this->faker->optional()->sentence(),
            'timeout_segundos' => $this->faker->randomElement([10, 15, 30]),
            'max_reintentos' => $this->faker->numberBetween(1, 5),
            'activo' => true,
            'eliminado' => false,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['activo' => false]);
    }

    public function paraEvento(string $evento): static
    {
        return $this->state(fn () => ['eventos' => [$evento]]);
    }

    public function todosLosEventos(): static
    {
        return $this->state(fn () => ['eventos' => Webhook::EVENTOS_DISPONIBLES]);
    }
}
