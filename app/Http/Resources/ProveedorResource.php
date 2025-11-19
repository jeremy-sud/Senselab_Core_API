<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProveedorResource extends JsonResource
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
            'tipo_identificacion' => $this->tipo_identificacion,
            'identificacion' => $this->identificacion,
            'nombre' => $this->nombre,
            'nombre_comercial' => $this->nombre_comercial,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'celular' => $this->celular,
            'direccion' => $this->direccion,
            'provincia' => $this->provincia,
            'canton' => $this->canton,
            'distrito' => $this->distrito,
            'codigo_postal' => $this->codigo_postal,
            'contacto_nombre' => $this->contacto_nombre,
            'contacto_email' => $this->contacto_email,
            'contacto_telefono' => $this->contacto_telefono,
            'dias_credito' => $this->dias_credito,
            'activo' => (bool) $this->activo,
            'observaciones' => $this->observaciones,
            
            // Productos asociados
            'productos' => $this->whenLoaded('productos', function () {
                return ProductoResource::collection($this->productos);
            }),
            
            // Estadísticas
            'ordenes_compra_count' => $this->whenCounted('ordenesCompra'),
            'cuentas_por_pagar_count' => $this->whenCounted('cuentasPorPagar'),
            
            // Timestamps
            'creado_en' => $this->creado_en?->toISOString(),
            'actualizado_en' => $this->actualizado_en?->toISOString(),
        ];
    }
}
