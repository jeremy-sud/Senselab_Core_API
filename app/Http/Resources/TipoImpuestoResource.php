<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para Tipo de Impuesto
 *
 * @package App\Http\Resources
 * @author Sistemas Ursol S.A. - Jeremy Arias Solano
 */
class TipoImpuestoResource extends JsonResource
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
            'codigo_hacienda' => $this->codigo_hacienda,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'comentario' => $this->Comentario,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Información adicional
            'es_iva' => $this->codigo_hacienda === '01',
            'es_exento' => in_array($this->codigo_hacienda, ['02', '03', '04']),
        ];
    }
}
