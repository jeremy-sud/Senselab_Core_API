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
            'nombre_comercial' => $this->faker->company(),
            'razon_social' => $this->faker->company() . ' S.A.',
            'tipo_documento_interno' => 'cedula_juridica',
            'num_identificacion_dgt' => $this->faker->unique()->numerify('##########'),
            'tipo_identificacion_dgt' => '02',
            'telefono' => $this->faker->phoneNumber(),
            'email' => $this->faker->companyEmail(),
            'actividad_economica_dgt' => $this->faker->numerify('######'),
            'direccion' => $this->faker->address(),
            'contacto_nombre' => $this->faker->name(),
            'contacto_telefono' => $this->faker->phoneNumber(),
            'contacto_email' => $this->faker->email(),
            'plazo_credito_dias' => $this->faker->numberBetween(0, 90),
            'limite_credito' => $this->faker->randomFloat(2, 100000, 5000000),
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
