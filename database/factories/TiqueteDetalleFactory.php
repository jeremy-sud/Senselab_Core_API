<?php

namespace Database\Factories;

use App\Models\TiqueteDetalle;
use App\Models\DetalleVenta;
use App\Models\HorarioRuta;
use Illuminate\Database\Eloquent\Factories\Factory;

class TiqueteDetalleFactory extends Factory
{
    protected $model = TiqueteDetalle::class;

    public function definition(): array
    {
        return [
            'detalle_venta_id' => DetalleVenta::factory(),
            'horario_ruta_id' => HorarioRuta::factory(),
            'asiento_numero' => $this->faker->numberBetween(1, 50),
            'nombre_pasajero' => $this->faker->name(),
            'identificacion_pasajero' => $this->faker->numerify('#########'),
            'precio_final_tiquete' => $this->faker->randomFloat(2, 500, 15000),
            'estado' => $this->faker->randomElement(['activo', 'usado', 'cancelado']),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
