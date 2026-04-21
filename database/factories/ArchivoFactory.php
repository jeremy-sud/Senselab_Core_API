<?php

namespace Database\Factories;

use App\Models\Archivo;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArchivoFactory extends Factory
{
    protected $model = Archivo::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'usuario_id' => Usuario::factory(),
            'entidad_tipo' => $this->faker->randomElement(['venta', 'compra', 'gasto']),
            'entidad_id' => $this->faker->randomNumber(5),
            'nombre_original' => $this->faker->word() . '.pdf',
            'nombre_almacenado' => $this->faker->uuid() . '.pdf',
            'ruta' => 'archivos/' . $this->faker->uuid() . '.pdf',
            'tipo_mime' => 'application/pdf',
            'extension' => 'pdf',
            'tamano_bytes' => $this->faker->numberBetween(1024, 10485760),
            'categoria' => $this->faker->randomElement(['factura', 'contrato', 'reporte']),
            'hash_sha256' => hash('sha256', $this->faker->uuid()),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
