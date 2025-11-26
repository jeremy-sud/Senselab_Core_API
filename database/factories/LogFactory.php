<?php

namespace Database\Factories;

use App\Models\LogAccesoSistema;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class LogFactory extends Factory
{
    protected $model = LogAccesoSistema::class;

    public function definition(): array
    {
        return [
            'nivel' => $this->faker->randomElement(['debug', 'info', 'warning', 'error', 'critical']),
            'mensaje' => $this->faker->sentence(),
            'contexto' => $this->faker->optional()->json(),
            'usuario_id' => Usuario::factory()->optional(),
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'url' => $this->faker->url(),
            'metodo' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE', 'PATCH']),
            'fecha' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
