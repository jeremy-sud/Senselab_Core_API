<?php

namespace Database\Factories;

use App\Models\TiqueteDetalle;
use App\Models\HorarioRuta;
use App\Models\Cliente;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class TiqueteDetalleFactory extends Factory
{
    protected $model = TiqueteDetalle::class;

    public function definition(): array
    {
        return [
            'horario_ruta_id' => HorarioRuta::factory(),
            'numero_tiquete' => $this->faker->unique()->numerify('TIQ-########'),
            'cliente_id' => Cliente::factory(),
            'vendedor_id' => Usuario::factory(),
            'numero_asiento' => $this->faker->numerify('A-##'),
            'fecha_viaje' => $this->faker->dateTimeBetween('now', '+1 month'),
            'precio' => $this->faker->randomFloat(2, 1000, 5000),
            'impuesto' => $this->faker->randomFloat(2, 130, 650),
            'total' => $this->faker->randomFloat(2, 1130, 5650),
            'estado' => $this->faker->randomElement(['reservado', 'pagado', 'usado', 'cancelado']),
            'observaciones' => $this->faker->optional()->sentence(),
        ];
    }
}
