<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmpresaResource extends JsonResource
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
            'nombre_comercial' => $this->nombre_comercial,
            'razon_social' => $this->razon_social,
            'identificacion' => $this->identificacion,
            'tipo_identificacion' => $this->tipo_identificacion,
            'regimen_tributario_id' => $this->regimen_tributario_id,
            'regimen_tributario' => $this->whenLoaded('regimenTributario', function () {
                return [
                    'id' => $this->regimenTributario->id,
                    'nombre' => $this->regimenTributario->nombre,
                    'codigo' => $this->regimenTributario->codigo,
                ];
            }),
            'telefono' => $this->telefono,
            'email' => $this->email,
            'sitio_web' => $this->sitio_web,
            'direccion' => $this->direccion,
            'provincia' => $this->provincia,
            'canton' => $this->canton,
            'distrito' => $this->distrito,
            'logo_url' => $this->logo_url,
            'activo' => (bool) $this->activo,
            'configuracion' => $this->configuracion,
            
            // Relaciones opcionales
            'sucursales_count' => $this->whenCounted('sucursales'),
            'usuarios_count' => $this->whenCounted('usuarios'),
            'productos_count' => $this->whenCounted('productos'),
            
            // Timestamps
            'creado_en' => $this->creado_en?->toISOString(),
            'actualizado_en' => $this->actualizado_en?->toISOString(),
        ];
    }
}
