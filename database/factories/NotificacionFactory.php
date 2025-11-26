<?php

namespace Database\Factories;

use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificacionFactory extends Factory
{
    protected $model = Notificacion::class;

    public function definition(): array
    {
        return [
            'usuario_id' => Usuario::factory(),
            'tipo' => $this->faker->randomElement(['info', 'advertencia', 'error', 'exito']),
            'titulo' => $this->faker->sentence(3),
            'mensaje' => $this->faker->sentence(),
            'categoria' => $this->faker->randomElement(['sistema', 'ventas', 'compras', 'facturacion']),
            'prioridad' => $this->faker->randomElement(['baja', 'media', 'alta']),
            'entidad_tipo' => $this->faker->optional()->randomElement(['App\\Models\\Venta', 'App\\Models\\OrdenCompra']),
            'entidad_id' => $this->faker->optional()->numberBetween(1, 100),
            'leida' => $this->faker->boolean(30),
            'archivada' => $this->faker->boolean(10),
            'fecha_leida' => null,
        ];
    }
}
