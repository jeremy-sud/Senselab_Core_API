<?php

namespace Database\Factories;

use App\Models\AuditoriaActividad;
use App\Models\Usuario;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditoriaActividadFactory extends Factory
{
    protected $model = AuditoriaActividad::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'empresa_id' => Empresa::factory(),
            'accion' => $this->faker->randomElement(['crear', 'actualizar', 'eliminar']),
            'tabla' => $this->faker->randomElement(['ventas', 'productos', 'clientes']),
            'registro_id' => $this->faker->randomNumber(5),
            'datos_anteriores' => ['campo' => 'valor_anterior'],
            'datos_nuevos' => ['campo' => 'valor_nuevo'],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}
