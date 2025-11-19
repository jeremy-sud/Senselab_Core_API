<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NominaEmpleadoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'periodo_nomina_id' => $this->periodo_nomina_id,
            'empleado_id' => $this->empleado_id,
            'salario_bruto' => (float) $this->salario_bruto,
            'horas_extras' => (float) $this->horas_extras,
            'monto_horas_extras' => (float) $this->monto_horas_extras,
            'bonificaciones' => (float) $this->bonificaciones,
            'total_devengado' => (float) $this->total_devengado,
            'deducciones_ccss' => (float) $this->deducciones_ccss,
            'deducciones_impuesto_renta' => (float) $this->deducciones_impuesto_renta,
            'otras_deducciones' => (float) $this->otras_deducciones,
            'total_deducciones' => (float) $this->total_deducciones,
            'salario_neto' => (float) $this->salario_neto,
            'observaciones' => $this->observaciones,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            'periodo' => $this->whenLoaded('periodoNomina'),
            'empleado' => $this->whenLoaded('empleado'),
        ];
    }
}
