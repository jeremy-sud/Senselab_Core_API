<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'nombre_comercial' => $this->faker->optional()->company(),
            'razon_social' => $this->faker->company() . ' S.A.',
            'num_identificacion_dgt' => $this->faker->unique()->numerify('3101######'),
            'tipo_identificacion' => '02',
            'actividad_economica_principal' => $this->faker->numerify('######'),
            'proveedor_sistemas' => $this->faker->optional()->company(),
            'direccion' => $this->faker->address(),
            'provincia' => $this->faker->randomElement(['San José', 'Alajuela', 'Cartago']),
            'canton' => $this->faker->city(),
            'distrito' => $this->faker->citySuffix(),
            'barrio' => $this->faker->optional()->streetName(),
            'registro_fiscal_8707' => $this->faker->optional()->numerify('####'),
            'telefono' => $this->faker->numerify('########'),
            'email' => $this->faker->unique()->companyEmail(),
            'subdominio' => $this->faker->unique()->slug(2),
            'certificado_llave_fe' => null,
            'pin_llave_fe_hash' => null,
            'prefijo_orden_compra' => $this->faker->optional()->numerify('OC-###'),
            'moneda_defecto' => 'CRC',
            'regimen_tributario_id' => null,
            'activo' => true,
        ];
    }
}
