<?php

namespace Database\Factories;

use App\Models\UrlShortener;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UrlShortenerFactory extends Factory
{
    protected $model = UrlShortener::class;

    public function definition(): array
    {
        return [
            'url_original' => $this->faker->url(),
            'codigo_corto' => Str::random(6),
            'usuario_id' => Usuario::factory()->optional(),
            'titulo' => $this->faker->optional()->sentence(3),
            'descripcion' => $this->faker->optional()->sentence(),
            'clicks' => $this->faker->numberBetween(0, 1000),
            'fecha_expiracion' => $this->faker->optional()->dateTimeBetween('now', '+1 year'),
            'activo' => $this->faker->boolean(90),
        ];
    }
}
