<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetencionImpuestoResource extends JsonResource
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
            'proveedor_id' => $this->proveedor_id,
            'compra_id' => $this->compra_id,
            'venta_id' => $this->venta_id,
            'tipo_retencion' => $this->tipo_retencion,
            'porcentaje_retencion' => (float) $this->porcentaje_retencion,
            'monto_base' => (float) $this->monto_base,
            'monto_retenido' => (float) $this->monto_retenido,
            'numero_comprobante' => $this->numero_comprobante,
            'fecha_retencion' => $this->fecha_retencion?->toISOString(),
            'periodo_declaracion' => $this->periodo_declaracion,
            'declaracion_id' => $this->declaracion_id,
            'declarado' => (bool) $this->declarado,
            'notas' => $this->notas,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa', fn() => [
                'id' => $this->empresa->id,
                'nombre' => $this->empresa->nombre,
            ]),
            'proveedor' => $this->whenLoaded('proveedor', fn() => [
                'id' => $this->proveedor->id,
                'nombre' => $this->proveedor->nombre,
            ]),
        ];
    }
}
