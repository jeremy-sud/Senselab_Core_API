<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeCertificadoDigitalResource extends JsonResource
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
            'empresa' => new EmpresaResource($this->whenLoaded('empresa')),
            
            // Información del certificado
            'nombre' => $this->nombre,
            'numero_serie' => $this->numero_serie,
            'emisor' => $this->emisor,
            'sujeto' => $this->sujeto,
            
            // Fechas de validez
            'fecha_emision' => $this->fecha_emision,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'dias_para_vencer' => $this->fecha_vencimiento ? now()->diffInDays($this->fecha_vencimiento, false) : null,
            'esta_vencido' => $this->fecha_vencimiento ? $this->fecha_vencimiento < now() : true,
            
            // Estado
            'activo' => $this->activo,
            'ambiente' => $this->ambiente,
            
            // Archivo (oculto por seguridad, solo metadata)
            'nombre_archivo_original' => $this->nombre_archivo_original,
            'ruta_archivo' => $this->when(
                $request->user()?->hasRole(['Administrador', 'Gerente']),
                $this->ruta_archivo
            ),
            
            // Contraseña del archivo .p12 (nunca se expone)
            // 'password_archivo' se omite intencionalmente por seguridad
            
            // Uso y estadísticas
            'comprobantes_firmados' => $this->whenCounted('comprobantes'),
            'ultimo_uso' => $this->when(
                $this->relationLoaded('comprobantes'),
                fn() => $this->comprobantes()->latest()->first()?->created_at
            ),
            
            // Observaciones
            'observaciones' => $this->observaciones,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
