<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuentaBancariaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'empresa_id' => $this->empresa_id,
            'banco' => $this->banco,
            'numero_cuenta' => $this->numero_cuenta_enmascarado,
            'iban' => $this->iban,
            'tipo_cuenta' => $this->tipo_cuenta,
            'moneda' => $this->moneda,
            'saldo_actual' => (float) $this->saldo_actual,
            'saldo_conciliado' => (float) $this->saldo_conciliado,
            'fecha_ultima_conciliacion' => $this->fecha_ultima_conciliacion?->toISOString(),
            'titular' => $this->titular,
            'es_principal' => (bool) $this->es_principal,
            'activa' => (bool) $this->activa,
            'notas' => $this->notas,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa', fn() => [
                'id' => $this->empresa->id,
                'nombre' => $this->empresa->nombre,
            ]),
        ];
    }
}
