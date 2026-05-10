<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para CAByS (Catálogo de Bienes y Servicios)
 *
 * @package App\Http\Resources
 * @author Senselab - Jeremy Arias Solano
 */
class CabyResource extends JsonResource
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
            'codigo' => $this->codigo,
            'descripcion' => $this->descripcion,
            'impuesto_iva_predeterminado' => $this->impuesto_iva_predeterminado ? (float) $this->impuesto_iva_predeterminado : null,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Información adicional
            'tiene_iva' => $this->impuesto_iva_predeterminado !== null && $this->impuesto_iva_predeterminado > 0,
            'productos_count' => $this->whenCounted('productos'),
            
            // Relaciones
            'productos' => $this->whenLoaded('productos', function () {
                return $this->productos->map(function ($producto) {
                    return [
                        'id' => $producto->id,
                        'nombre' => $producto->nombre,
                        'codigo' => $producto->codigo
                    ];
                });
            }),
        ];
    }
}
