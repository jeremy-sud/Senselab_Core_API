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
            'tipo_documento_dgt' => $this->tipo_documento_dgt,
            'tipo_documento_nombre' => $this->obtenerNombreTipoDocumento(),
            'prefijo' => $this->prefijo,
            'consecutivo_actual' => $this->consecutivo_actual,
            'consecutivo_formateado' => $this->prefijo . str_pad($this->consecutivo_actual, 10, '0', STR_PAD_LEFT),
            'estado' => $this->estado,
            'fecha_autorizacion' => $this->fecha_autorizacion,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa'),
            'sucursal' => $this->whenLoaded('sucursal'),
        ];
    }

    /**
     * Obtener el nombre del tipo de documento DGT.
     */
    private function obtenerNombreTipoDocumento(): string
    {
        $tipos = [
            '01' => 'Factura Electrónica',
            '02' => 'Nota de Débito',
            '03' => 'Nota de Crédito',
            '04' => 'Tiquete Electrónico',
            '08' => 'Factura Electrónica de Compra',
            '09' => 'Factura Electrónica de Exportación',
        ];

        return $tipos[$this->tipo_documento_dgt] ?? 'Desconocido';
    }
}
