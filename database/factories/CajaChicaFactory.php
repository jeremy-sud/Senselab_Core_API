<?php

namespace Database\Factories;

use App\Models\CajaChica;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class CajaChicaFactory extends Factory
{
    protected $model = CajaChica::class;

    public function definition(): array
    {
        $fechaApertura = $this->faker->dateTimeBetween('-3 months', 'now');
        $estado = $this->faker->randomElement(['Abierta', 'Cerrada']);

        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => 'Caja Chica ' . $this->faker->words(2, true),
            'monto_inicial' => $montoInicial = $this->faker->randomFloat(2, 5000, 50000),
            'saldo_actual' => $this->faker->randomFloat(2, 0, $montoInicial),
            'responsable_id' => Usuario::factory(),
            'fecha_apertura' => $fechaApertura,
            'fecha_cierre' => $estado !== 'Abierta' ? $this->faker->dateTimeBetween($fechaApertura, 'now') : null,
            'estado' => $estado,
            'observaciones' => $this->faker->optional()->sentence(),
            'activo' => true,
        ];
    }

    public function abierta(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'Abierta',
            'fecha_cierre' => null,
        ]);
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'Cerrada',
            'fecha_cierre' => now(),
        ]);
    }
}
