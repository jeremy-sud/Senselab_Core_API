<?php

namespace Database\Factories;

use App\Models\LogAccesoSistema;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class LogAccesoSistemaFactory extends Factory
{
    protected $model = LogAccesoSistema::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'accion' => $this->faker->randomElement(['login', 'logout', 'intento_fallido']),
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'exitoso' => $this->faker->boolean(85),
            'mensaje' => $this->faker->optional()->sentence(),
            'fecha' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
