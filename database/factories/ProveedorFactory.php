<?php

namespace Database\Factories;

use App\Models\Proveedor;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProveedorFactory extends Factory
{
    protected $model = Proveedor::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'tipo_identificacion' => $this->faker->randomElement(['01', '02', '03', '04']), // 01=Física, 02=Jurídica, 03=DIMEX, 04=NITE
            'numero_identificacion' => $this->faker->unique()->numerify('##########'),
            'nombre' => $this->faker->company() . ' S.A.',
            'nombre_comercial' => $this->faker->optional()->company(),
            'email' => $this->faker->optional()->companyEmail(),
            'telefono' => $this->faker->optional()->phoneNumber(),
            'direccion' => $this->faker->optional()->address(),
            'provincia' => $this->faker->optional()->randomElement(['San José', 'Alajuela', 'Cartago', 'Heredia', 'Guanacaste', 'Puntarenas', 'Limón']),
            'canton' => $this->faker->optional()->city(),
            'distrito' => $this->faker->optional()->citySuffix(),
            'limite_credito' => $this->faker->randomFloat(2, 100000, 5000000),
            'plazo_credito_dias' => $this->faker->numberBetween(0, 90),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
