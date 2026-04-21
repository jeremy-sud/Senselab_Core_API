<?php

namespace Database\Factories;

use App\Models\ConsecutivoFe;
use App\Models\Empresa;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConsecutivoFeFactory extends Factory
{
    protected $model = ConsecutivoFe::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'sucursal_id' => Sucursal::factory(),
            'tipo_comprobante' => $this->faker->randomElement(['01', '02', '03', '04']),
            'prefijo' => $this->faker->numerify('###'),
            'consecutivo_actual' => 1,
            'consecutivo_inicial' => 1,
            'consecutivo_final' => 99999999,
            'fecha_resolucion' => $this->faker->date(),
            'numero_resolucion' => $this->faker->numerify('DGT-R-###-####'),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
