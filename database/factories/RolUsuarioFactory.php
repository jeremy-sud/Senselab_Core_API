<?php

namespace Database\Factories;

use App\Models\RolUsuario;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class RolUsuarioFactory extends Factory
{
    protected $model = RolUsuario::class;

    public function definition(): array
    {
        return [
            'rol_id' => Rol::factory(),
            'usuario_id' => Usuario::factory(),
            'asignado_por' => Usuario::factory(),
            'fecha_asignacion' => $this->faker->dateTimeThisYear(),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
