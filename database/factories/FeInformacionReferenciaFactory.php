<?php

namespace Database\Factories;

use App\Models\ComprobanteElectronicoFe;
use App\Models\FeInformacionReferencia;
use Illuminate\Database\Eloquent\Factories\Factory;

class FeInformacionReferenciaFactory extends Factory
{
    protected $model = FeInformacionReferencia::class;

    public function definition(): array
    {
        return [
            'comprobante_id' => ComprobanteElectronicoFe::factory(),
            'tipo_doc' => '01',
            'numero' => '5' . str_pad($this->faker->numberBetween(1, 999999999), 49, '0', STR_PAD_LEFT),
            'fecha_emision' => $this->faker->dateTimeBetween('-6 months', '-1 day'),
            'codigo' => '01',
            'razon' => $this->faker->sentence(5),
        ];
    }

    public function anulacion(): static
    {
        return $this->state(fn () => [
            'codigo' => '01',
            'razon' => 'Anulación de comprobante',
        ]);
    }

    public function correccion(): static
    {
        return $this->state(fn () => [
            'codigo' => '02',
            'razon' => 'Corrección de montos',
        ]);
    }

    public function tipoOtro(): static
    {
        return $this->state(fn () => [
            'tipo_doc' => '99',
            'tipo_doc_otro' => 'Documento especial',
            'codigo' => '99',
            'codigo_referencia_otro' => 'Referencia especial',
        ]);
    }
}
