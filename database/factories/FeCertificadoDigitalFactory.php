<?php

namespace Database\Factories;

use App\Models\FeCertificadoDigital;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeCertificadoDigitalFactory extends Factory
{
    protected $model = FeCertificadoDigital::class;

    public function definition(): array
    {
        return [
            'empresa_id' => Empresa::factory(),
            'nombre' => $this->faker->company . ' - Certificado Digital',
            'tipo' => 'p12',
            'ruta_archivo' => 'certificates/test_cert_' . $this->faker->uuid . '.p12',
            'password_encrypted' => base64_encode('test_password'),
            'fecha_emision' => now()->subYear(),
            'fecha_vencimiento' => now()->addYear(),
            'numero_serie' => strtoupper($this->faker->bothify('??##??##??##??##')),
            'emisor' => 'CN=Test CA, O=Test Organization',
            'sujeto' => 'CN=' . $this->faker->company,
            'activo' => true,
            'valido' => true,
            'ambiente' => 'sandbox',
        ];
    }

    public function vencido(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_vencimiento' => now()->subDay(),
            'activo' => false,
        ]);
    }

    public function proximoVencimiento(): static
    {
        return $this->state(fn (array $attributes) => [
            'fecha_vencimiento' => now()->addDays(15),
        ]);
    }
}
