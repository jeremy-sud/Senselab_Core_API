<?php

namespace Database\Factories;

use App\Models\ModeloBus;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModeloBusFactory extends Factory
{
    protected $model = ModeloBus::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->unique()->words(2, true),
        ];
    }
}
