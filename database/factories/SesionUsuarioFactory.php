<?php

namespace Database\Factories;

use App\Models\SesionUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class SesionUsuarioFactory extends Factory
{
    protected $model = SesionUsuario::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'token_hash' => hash('sha256', $this->faker->uuid()),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'ultimo_acceso' => now(),
            'activo' => true,
        ];
    }
}
