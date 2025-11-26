<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DetalleOrdenCompraResource extends JsonResource
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
            'orden_compra_id' => $this->orden_compra_id,
            'producto_id' => $this->producto_id,
            'numero_linea' => $this->numero_linea,
            'cantidad' => $this->cantidad,
            'precio_unitario' => $this->precio_unitario,
            'porcentaje_impuesto' => $this->porcentaje_impuesto,
            'monto_impuesto' => $this->monto_impuesto,
            'subtotal_linea' => $this->subtotal_linea,
            'total_linea' => $this->total_linea,
            'detalle_adicional' => $this->detalle_adicional,
            'activo' => $this->activo,
            'orden_compra' => new OrdenCompraResource($this->whenLoaded('ordenCompra')),
            'producto' => new ProductoResource($this->whenLoaded('producto')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
