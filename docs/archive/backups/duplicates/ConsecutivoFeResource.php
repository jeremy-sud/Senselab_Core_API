<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsecutivoFeResource extends JsonResource
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
            'sucursal_id' => $this->sucursal_id,
            'tipo_comprobante' => $this->tipo_comprobante,
            'prefijo' => $this->prefijo,
            'consecutivo_inicial' => $this->consecutivo_inicial,
            'consecutivo_final' => $this->consecutivo_final,
            'consecutivo_actual' => $this->consecutivo_actual,
            'fecha_resolucion' => $this->fecha_resolucion,
            'numero_resolucion' => $this->numero_resolucion,
            'activo' => $this->activo,
            'empresa' => new EmpresaResource($this->whenLoaded('empresa')),
            'sucursal' => new SucursalResource($this->whenLoaded('sucursal')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
