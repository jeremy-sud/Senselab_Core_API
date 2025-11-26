<?php

namespace Database\Factories;

use App\Models\FeOAuthToken;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeOAuthTokenFactory extends Factory
{
    protected $model = FeOAuthToken::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'access_token' => $this->faker->sha256(),
            'refresh_token' => $this->faker->sha256(),
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 hour'),
            'scope' => $this->faker->optional()->word(),
        ];
    }
}
