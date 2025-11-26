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
        $inicio = $this->faker->dateTimeThisMonth();
        
        return [
            'usuario_id' => Usuario::factory(),
            'token' => $this->faker->sha256(),
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'fecha_inicio' => $inicio,
            'fecha_fin' => $this->faker->optional()->dateTimeBetween($inicio, '+8 hours'),
            'activa' => $this->faker->boolean(70),
        ];
    }
}
