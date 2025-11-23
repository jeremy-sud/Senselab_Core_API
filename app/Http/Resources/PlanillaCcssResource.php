<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanillaCcssResource extends JsonResource
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
            'periodo_nomina_id' => $this->periodo_nomina_id,
            'periodo' => $this->periodo,
            'fecha_generacion' => $this->fecha_generacion?->toISOString(),
            'fecha_presentacion' => $this->fecha_presentacion?->toISOString(),
            'numero_planilla' => $this->numero_planilla,
            'total_empleados' => $this->total_empleados,
            'total_salarios' => (float) $this->total_salarios,
            'total_cuota_obrera' => (float) $this->total_cuota_obrera,
            'total_cuota_patronal' => (float) $this->total_cuota_patronal,
            'total_a_pagar' => (float) $this->total_a_pagar,
            'archivo_xml' => $this->archivo_xml,
            'archivo_pdf' => $this->archivo_pdf,
            'estado' => $this->estado,
            'fecha_pago' => $this->fecha_pago?->toISOString(),
            'notas' => $this->notas,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relaciones
            'empresa' => $this->whenLoaded('empresa', fn() => [
                'id' => $this->empresa->id,
                'nombre' => $this->empresa->nombre,
            ]),
            'periodo_nomina' => $this->whenLoaded('periodoNomina', fn() => [
                'id' => $this->periodoNomina->id,
                'fecha_inicio' => $this->periodoNomina->fecha_inicio?->toISOString(),
                'fecha_fin' => $this->periodoNomina->fecha_fin?->toISOString(),
            ]),
        ];
    }
}
