<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\ReporteProgramado;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReporteProgramado>
 */
class ReporteProgramadoFactory extends Factory
{
    protected $model = ReporteProgramado::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'usuario_id' => Usuario::factory(),
            'nombre' => $this->faker->sentence(3),
            'tipo_reporte' => $this->faker->randomElement(['estado_resultados', 'balance_general', 'flujo_caja']),
            'frecuencia' => $this->faker->randomElement(['diario', 'semanal', 'mensual']),
            'formato' => $this->faker->randomElement(['pdf', 'excel', 'csv']),
            'filtros' => ['moneda' => 'CRC'],
            'destinatarios' => [$this->faker->safeEmail()],
            'dia_semana' => null,
            'dia_mes' => null,
            'hora_envio' => '07:00',
            'ultima_ejecucion' => null,
            'proxima_ejecucion' => now()->addDay(),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
