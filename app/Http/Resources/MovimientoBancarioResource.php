<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoBancarioResource extends JsonResource
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
            'cuenta_bancaria_id' => $this->cuenta_bancaria_id,
            'empresa_id' => $this->empresa_id,
            'fecha_movimiento' => $this->fecha_movimiento?->toISOString(),
            'fecha_valor' => $this->fecha_valor?->toISOString(),
            'tipo_movimiento' => $this->tipo_movimiento,
            'numero_referencia' => $this->numero_referencia,
            'descripcion' => $this->descripcion,
            'monto' => (float) $this->monto,
            'saldo_despues' => (float) $this->saldo_despues,
            'beneficiario' => $this->beneficiario,
            'conciliado' => (bool) $this->conciliado,
            'fecha_conciliacion' => $this->fecha_conciliacion?->toISOString(),
            'asiento_contable_id' => $this->asiento_contable_id,
            'notas' => $this->notas,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relaciones
            'cuenta_bancaria' => $this->whenLoaded('cuentaBancaria', fn() => [
                'id' => $this->cuentaBancaria->id,
                'banco' => $this->cuentaBancaria->banco,
                'numero_cuenta' => $this->cuentaBancaria->numero_cuenta_enmascarado,
            ]),
            'empresa' => $this->whenLoaded('empresa', fn() => [
                'id' => $this->empresa->id,
                'nombre' => $this->empresa->nombre,
            ]),
            'asiento_contable' => $this->whenLoaded('asientoContable', fn() => [
                'id' => $this->asientoContable->id,
                'numero_asiento' => $this->asientoContable->numero_asiento,
            ]),
        ];
    }
}
