<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Detalle de Asiento Contable
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class DetalleAsientoResource extends JsonResource
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
            'asiento_contable_id' => $this->asiento_contable_id,
            'asiento_contable' => $this->whenLoaded('asientoContable', function () {
                return [
                    'id' => $this->asientoContable->id,
                    'fecha' => $this->asientoContable->fecha,
                    'descripcion' => $this->asientoContable->descripcion,
                    'estado' => $this->asientoContable->estado
                ];
            }),
            'cuenta_contable_id' => $this->cuenta_contable_id,
            'cuenta_contable' => $this->whenLoaded('cuentaContable', function () {
                return [
                    'id' => $this->cuentaContable->id,
                    'numero_cuenta' => $this->cuentaContable->numero_cuenta,
                    'nombre_cuenta' => $this->cuentaContable->nombre_cuenta,
                    'saldo_actual' => number_format($this->cuentaContable->saldo_actual ?? 0, 2)
                ];
            }),
            'debe' => number_format($this->debe, 2),
            'haber' => number_format($this->haber, 2),
            'descripcion' => $this->descripcion,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
