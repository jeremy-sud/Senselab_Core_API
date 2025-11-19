<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Presupuesto Financiero
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class PresupuestoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $totalPresupuestado = $this->whenLoaded('detalles', function () {
            return $this->detalles->sum('monto_presupuestado');
        }, 0);

        return [
            'id' => $this->id,
            'empresa_id' => $this->empresa_id,
            'nombre' => $this->nombre,
            'periodo_inicio' => $this->periodo_inicio,
            'periodo_fin' => $this->periodo_fin,
            'estado' => $this->estado,
            'total_presupuestado' => is_numeric($totalPresupuestado) ? number_format($totalPresupuestado, 2) : '0.00',
            'total_cuentas' => $this->whenCounted('detalles'),
            'detalles' => DetallePresupuestoResource::collection($this->whenLoaded('detalles')),
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
