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
            'numero_cuenta' => $this->resource->getNumeroCuentaEnmascarado(),
            'iban' => $this->iban,
            'tipo_cuenta' => $this->tipo_cuenta,
            'moneda' => $this->moneda,
            'saldo_actual' => (float) $this->saldo_actual,
            'cuenta_contable_id' => $this->cuenta_contable_id,
            'sucursal_banco' => $this->sucursal_banco,
            'contacto_ejecutivo' => $this->contacto_ejecutivo,
            'telefono_ejecutivo' => $this->telefono_ejecutivo,
            'es_principal' => (bool) $this->es_principal,
            'activa' => (bool) $this->activa,
            'notas' => $this->notas,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa', fn() => [
                'id' => $this->empresa->id,
                'nombre' => $this->empresa->nombre,
            ]),
        ];
    }
}
