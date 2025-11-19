<?php

namespace Database\Factories;

use App\Models\Usuario;
use App\Models\Empresa;
use App\Models\Cargo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->firstName(),
            'apellidos' => $this->faker->lastName() . ' ' . $this->faker->lastName(),
            'cargo_id' => Cargo::inRandomOrder()->first()?->id,
            'email' => $this->faker->unique()->safeEmail(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'empresa_id' => Empresa::factory(),
            'telefono' => $this->faker->phoneNumber(),
            'direccion' => $this->faker->address(),
            'activo' => true,
            'eliminado' => false,
        ];
    }

    public function withPassword(string $password): static
    {
        return $this->state(fn (array $attributes) => [
            'password_hash' => Hash::make($password),
        ]);
    }
}
