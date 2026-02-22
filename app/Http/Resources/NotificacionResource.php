<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificacionResource extends JsonResource
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
            
            // Usuario receptor
            'usuario_id' => $this->usuario_id,
            'usuario' => new UsuarioResource($this->whenLoaded('usuario')),
            
            // Tipo de notificación
            'tipo' => $this->tipo,
            'categoria' => $this->categoria,
            'prioridad' => $this->prioridad,
            
            // Contenido
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'icono' => $this->icono,
            'color' => $this->color,
            
            // Datos adicionales (JSON)
            'datos' => $this->datos,
            
            // Entidad relacionada (morfable)
            'entidad_tipo' => $this->entidad_tipo,
            'entidad_id' => $this->entidad_id,
            'entidad' => $this->when(
                $this->entidad,
                fn() => $this->getEntidadResource()
            ),
            
            // Acciones
            'accion_url' => $this->accion_url,
            'accion_texto' => $this->accion_texto,
            
            // Estado
            'leida' => $this->leida,
            'fecha_lectura' => $this->fecha_lectura,
            'archivada' => $this->archivada,
            
            // Canal de envío
            'canal' => $this->canal,
            'enviada_email' => $this->enviada_email,
            'enviada_push' => $this->enviada_push,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'expires_at' => $this->expires_at,
            
            // Información adicional
            'tiempo_transcurrido' => $this->created_at?->diffForHumans(),
            'esta_expirada' => $this->expires_at ? $this->expires_at < now() : false,
        ];
    }

    /**
     * Get the appropriate resource for the related entity.
     */
    protected function getEntidadResource(): mixed
    {
        if (!$this->entidad) {
            return null;
        }

        return match ($this->entidad_tipo) {
            'App\Models\ComprobanteElectronicoFe' => new ComprobanteElectronicoFeResource($this->entidad),
            'App\Models\Venta' => new VentaResource($this->entidad),
            'App\Models\OrdenCompra' => new OrdenCompraResource($this->entidad),
            'App\Models\CuentaPorCobrar' => new CuentaPorCobrarResource($this->entidad),
            'App\Models\CuentaPorPagar' => new CuentaPorPagarResource($this->entidad),
            'App\Models\Usuario' => new UsuarioResource($this->entidad),
            default => [
                'id' => $this->entidad->id ?? null,
                'tipo' => class_basename($this->entidad_tipo),
            ],
        };
    }
}
