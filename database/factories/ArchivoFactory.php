<?php

namespace Database\Factories;

use App\Models\Archivo;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArchivoFactory extends Factory
{
    protected $model = Archivo::class;

    public function definition(): array
    {
        $extension = $this->faker->randomElement(['pdf', 'jpg', 'png', 'docx', 'xlsx']);
        $tamano = $this->faker->numberBetween(1024, 10485760); // 1KB - 10MB
        
        return [
            'nombre_original' => $this->faker->word() . '.' . $extension,
            'nombre_almacenado' => $this->faker->uuid() . '.' . $extension,
            'ruta' => 'uploads/' . date('Y/m/'),
            'extension' => $extension,
            'mime_type' => $this->faker->mimeType(),
            'tamano' => $tamano,
            'entidad_tipo' => $this->faker->randomElement(['App\\Models\\Cliente', 'App\\Models\\Producto', 'App\\Models\\Venta']),
            'entidad_id' => $this->faker->numberBetween(1, 100),
            'usuario_id' => Usuario::factory(),
            'descripcion' => $this->faker->optional()->sentence(),
        ];
    }
}
