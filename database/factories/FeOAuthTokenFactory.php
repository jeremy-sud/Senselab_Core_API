<?php

namespace Database\Factories;

use App\Models\FeOAuthToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeOAuthTokenFactory extends Factory
{
    protected $model = FeOAuthToken::class;

    public function definition(): array
    {
        return [
            'ambiente' => $this->faker->randomElement(['stag', 'prod']),
            'access_token' => $this->faker->sha256(),
            'token_type' => 'Bearer',
            'expires_in' => 300,
            'expires_at' => now()->addMinutes(5),
            'refresh_token' => $this->faker->sha256(),
            'scope' => null,
            'activo' => true,
            'uso_contador' => 0,
            'ultimo_uso' => null,
            'metadata' => null,
        ];
    }
}
