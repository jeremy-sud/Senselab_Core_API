<?php

namespace Database\Factories;

use App\Models\Empresa;
use App\Models\RegimenTributario;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmpresaFactory extends Factory
{
    protected $model = Empresa::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'nombre_comercial' => $this->faker->companySuffix() . ' ' . $this->faker->word(),
            'razon_social' => $this->faker->company() . ' S.A.',
            'num_identificacion_dgt' => $this->faker->unique()->numerify('##########'),
            'tipo_identificacion' => '02', // Cédula Jurídica
            'actividad_economica_principal' => $this->faker->numerify('######'),
            'proveedor_sistemas' => 'SISTEMA ERP', // Nuevo en v4.4 - Identificación del proveedor del sistema
            'direccion' => $this->faker->address(),
            'provincia' => (string) $this->faker->numberBetween(1, 7),
            'canton' => (string) $this->faker->numberBetween(1, 20),
            'distrito' => (string) $this->faker->numberBetween(1, 50),
            'telefono' => $this->faker->numerify('####-####'),
            'email' => $this->faker->unique()->companyEmail(),
            'certificado_llave_fe' => null,
            'pin_llave_fe_hash' => null,
            'prefijo_orden_compra' => strtoupper($this->faker->lexify('??')),
            'moneda_defecto' => 'CRC',
            'regimen_tributario_id' => RegimenTributario::inRandomOrder()->first()?->id ?? 1,
            'activo' => true,
            'eliminado' => false,
        ];
    }
}
