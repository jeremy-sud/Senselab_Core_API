<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Detalle de Entrada de Inventario
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class DetalleEntradaInventarioResource extends JsonResource
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
            'entrada_inventario_id' => $this->entrada_inventario_id,
            'producto_id' => $this->producto_id,
            'producto' => $this->whenLoaded('producto', function () {
                return [
                    'id' => $this->producto->id,
                    'nombre' => $this->producto->nombre,
                    'codigo' => $this->producto->codigo,
                    'unidad_medida' => $this->producto->unidadMedida?->nombre,
                    'categoria' => $this->producto->categoriaProducto?->nombre
                ];
            }),
            'cantidad' => number_format($this->cantidad, 2),
            'costo_unitario' => number_format($this->costo_unitario, 2),
            'subtotal' => number_format($this->subtotal, 2),
            'lote' => $this->lote,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
