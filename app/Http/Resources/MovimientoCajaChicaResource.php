<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovimientoCajaChicaResource extends JsonResource
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
            'caja_chica_id' => $this->caja_chica_id,
            'fecha_movimiento' => $this->fecha_movimiento,
            'tipo_movimiento' => $this->tipo_movimiento,
            'monto' => (float) $this->monto,
            'numero_comprobante' => $this->numero_comprobante,
            'concepto' => $this->concepto,
            'cuenta_contable_id' => $this->cuenta_contable_id,
            'activo' => (bool) $this->activo,
            'eliminado' => (bool) $this->eliminado,
            'creado_en' => $this->creado_en,
            'actualizado_en' => $this->actualizado_en,
            
            // Relaciones
            'caja_chica' => $this->whenLoaded('cajaChica', function () {
                return [
                    'id' => $this->cajaChica->id,
                    'nombre' => $this->cajaChica->nombre,
                    'estado' => $this->cajaChica->estado,
                ];
            }),
            
            'cuenta_contable' => $this->whenLoaded('cuentaContable', function () {
                return [
                    'id' => $this->cuentaContable->id,
                    'codigo' => $this->cuentaContable->codigo,
                    'nombre' => $this->cuentaContable->nombre,
                ];
            }),
        ];
    }
}
