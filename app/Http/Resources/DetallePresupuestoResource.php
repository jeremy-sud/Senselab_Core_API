<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Detalle de Presupuesto
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class DetallePresupuestoResource extends JsonResource
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
            'presupuesto_id' => $this->presupuesto_id,
            'cuenta_contable_id' => $this->cuenta_contable_id,
            'cuenta_contable' => $this->whenLoaded('cuentaContable', function () {
                return [
                    'id' => $this->cuentaContable->id,
                    'codigo' => $this->cuentaContable->codigo,
                    'nombre' => $this->cuentaContable->nombre,
                    'tipo_cuenta' => $this->cuentaContable->tipoCuenta?->nombre
                ];
            }),
            'monto_presupuestado' => number_format($this->monto_presupuestado, 2),
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
