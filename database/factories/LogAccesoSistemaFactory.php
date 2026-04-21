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
            'email' => $this->faker->safeEmail(),
            'tipo_evento' => $this->faker->randomElement(['login_exitoso', 'login_fallido', 'logout']),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'metodo_autenticacion' => 'sanctum',
            'razon_fallo' => null,
            'sesion_id' => $this->faker->uuid(),
            'duracion_sesion' => $this->faker->numberBetween(60, 7200),
            'pais' => 'Costa Rica',
            'ciudad' => $this->faker->city(),
            'creado_en' => now(),
        ];
    }
}
