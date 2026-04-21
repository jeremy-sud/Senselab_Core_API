<?php

namespace Database\Factories;

use App\Models\RolUsuario;
use App\Models\Usuario;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Factories\Factory;

class RolUsuarioFactory extends Factory
{
    protected $model = RolUsuario::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'rol_id' => Rol::factory(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
