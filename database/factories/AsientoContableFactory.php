<?php

namespace Database\Factories;

use App\Models\AsientoContable;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class AsientoContableFactory extends Factory
{
    protected $model = AsientoContable::class;

    public function definition(): array
    {
        $fecha = $this->faker->dateTimeBetween('-1 year', 'now');
        
        return [
            'empresa_id' => Empresa::factory(),
            'numero_asiento' => $this->faker->unique()->numberBetween(1, 10000),
            'fecha' => $fecha,
            'tipo' => $this->faker->randomElement(['ingreso', 'egreso', 'diario', 'ajuste', 'apertura', 'cierre']),
            'descripcion' => $this->faker->sentence(6),
            'referencia' => $this->faker->optional()->numerify('REF-####'),
            'total_debe' => $monto = $this->faker->randomFloat(2, 100, 50000),
            'total_haber' => $monto,
            'estado' => $this->faker->randomElement(['borrador', 'registrado', 'mayorizado', 'anulado']),
            'mayorizado' => $this->faker->boolean(60),
            'fecha_mayorizacion' => fn (array $attributes) => 
                $attributes['mayorizado'] ? $this->faker->dateTimeBetween($fecha, 'now') : null,
            'usuario_id' => Usuario::factory(),
            'observaciones' => $this->faker->optional()->sentence(10),
        ];
    }

    public function borrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'borrador',
            'mayorizado' => false,
            'fecha_mayorizacion' => null,
        ]);
    }

    public function mayorizado(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'mayorizado',
            'mayorizado' => true,
            'fecha_mayorizacion' => now(),
        ]);
    }
}
