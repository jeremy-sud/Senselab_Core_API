<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Detalle de Salida de Inventario
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class DetalleSalidaInventarioResource extends JsonResource
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
            'salida_inventario_id' => $this->salida_inventario_id,
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
            'costo_unitario_salida' => number_format($this->costo_unitario_salida, 2),
            'subtotal' => number_format($this->subtotal, 2),
            'lote' => $this->lote,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'activo' => (bool) $this->activo,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
