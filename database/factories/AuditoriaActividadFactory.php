<?php

namespace Database\Factories;

use App\Models\AuditoriaActividad;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditoriaActividadFactory extends Factory
{
    protected $model = AuditoriaActividad::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'accion' => $this->faker->randomElement(['crear', 'actualizar', 'eliminar', 'ver']),
            'entidad_tipo' => $this->faker->randomElement(['App\\Models\\Cliente', 'App\\Models\\Producto', 'App\\Models\\Venta']),
            'entidad_id' => $this->faker->numberBetween(1, 100),
            'datos_anteriores' => $this->faker->optional()->json(),
            'datos_nuevos' => $this->faker->optional()->json(),
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'fecha' => $this->faker->dateTimeThisMonth(),
        ];
    }
}
