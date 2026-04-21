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
        return [
            'empresa_id' => Empresa::factory(),
            'numero_asiento' => $this->faker->unique()->numerify('ASI-######'),
            'fecha_asiento' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'tipo_asiento' => $this->faker->randomElement(['Diario', 'Ajuste', 'Cierre']),
            'origen' => $this->faker->randomElement(['manual', 'venta', 'compra']),
            'documento_origen_id' => null,
            'concepto' => $this->faker->sentence(),
            'total_debe' => $monto = $this->faker->randomFloat(2, 100, 50000),
            'total_haber' => $monto,
            'estado' => 'Cuadrado',
            'usuario_id' => Usuario::factory(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
    public function borrador(): static
    {
        return $this->state(fn (array $attributes) => [
            'estado' => 'Borrador',
        ]);
    }
}
