<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResource extends JsonResource
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
            'tipo_cliente' => $this->tipo_cliente,
            'limite_credito' => $this->limite_credito ? (float) $this->limite_credito : null,
            'dias_credito' => $this->dias_credito,
            'descuento_general' => $this->descuento_general ? (float) $this->descuento_general : null,
            'activo' => (bool) $this->activo,
            'observaciones' => $this->observaciones,
            
            // Estadísticas (si están cargadas)
            'ventas_count' => $this->whenCounted('ventas'),
            'cuentas_por_cobrar_count' => $this->whenCounted('cuentasPorCobrar'),
            
            // Timestamps
            'creado_en' => $this->creado_en?->toISOString(),
            'actualizado_en' => $this->actualizado_en?->toISOString(),
        ];
    }
}
