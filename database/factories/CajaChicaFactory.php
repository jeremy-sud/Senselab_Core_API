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
        $estado = $this->faker->randomElement(['abierta', 'cerrada', 'liquidada']);
        
        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => 'Caja Chica ' . $this->faker->words(2, true),
            'codigo' => strtoupper($this->faker->unique()->lexify('CC-????')),
            'monto_asignado' => $montoAsignado = $this->faker->randomFloat(2, 5000, 50000),
            'saldo_actual' => $this->faker->randomFloat(2, 0, $montoAsignado),
            'responsable_id' => Usuario::factory(),
            'fecha_apertura' => $fechaApertura,
            'fecha_cierre' => $estado !== 'abierta' ? $this->faker->dateTimeBetween($fechaApertura, 'now') : null,
            'fecha_liquidacion' => $estado === 'liquidada' ? $this->faker->dateTimeBetween($fechaApertura, 'now') : null,
            'estado' => $estado,
            'periodo' => $this->faker->optional()->monthName(),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }

    public function abierta(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'abierta',
            'fecha_cierre' => null,
            'fecha_liquidacion' => null,
        ]);
    }

    public function cerrada(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'cerrada',
            'fecha_cierre' => now(),
            'fecha_liquidacion' => null,
        ]);
    }
}
