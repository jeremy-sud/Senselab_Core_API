<?php

namespace Database\Factories;

use App\Models\UrlShortener;
use App\Models\Empresa;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class UrlShortenerFactory extends Factory
{
    protected $model = UrlShortener::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'usuario_id' => Usuario::factory(),
            'url_original' => $this->faker->url(),
            'url_corta' => config('app.url') . '/' . $this->faker->unique()->regexify('[a-zA-Z0-9]{6}'),
            'slug' => $this->faker->unique()->regexify('[a-zA-Z0-9]{6}'),
            'clicks' => 0,
            'descripcion' => $this->faker->optional()->sentence(),
            'expira_en' => $this->faker->optional()->dateTimeBetween('+1 month', '+1 year'),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
