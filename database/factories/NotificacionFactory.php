<?php

namespace Database\Factories;

use App\Models\Notificacion;
use App\Models\Usuario;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificacionFactory extends Factory
{
    protected $model = Notificacion::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'empresa_id' => Empresa::factory(),
            'tipo' => $this->faker->randomElement(['info', 'warning', 'error', 'success']),
            'titulo' => $this->faker->sentence(4),
            'mensaje' => $this->faker->paragraph(),
            'datos' => null,
            'leida' => false,
            'leida_en' => null,
            'url' => $this->faker->optional()->url(),
            'prioridad' => $this->faker->randomElement([0, 1, 2]),
        ];
    }
}
