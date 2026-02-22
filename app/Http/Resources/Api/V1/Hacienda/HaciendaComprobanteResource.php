<?php

namespace App\Http\Resources\Api\V1\Hacienda;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API Resource para HaciendaComprobante
 *
 * Formatea la respuesta JSON para los endpoints de Hacienda
 */
class HaciendaComprobanteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'comprobante_id' => $this->comprobante_id,
            'empresa_id' => $this->empresa_id,
            'clave' => $this->clave,
            'tipo_comprobante' => $this->tipo_comprobante,
            'tipo_label' => $this->getTipoLabel(),
            'estado' => $this->estado,
            'estado_label' => $this->getEstadoLabel(),
            'numero_secuencia' => $this->numero_secuencia,
            'fecha_respuesta' => $this->fecha_respuesta?->toIso8601String(),
            'mensaje_error' => $this->mensaje_error,
            'respuesta_hacienda' => $this->when(
                $request->user()?->can('view_hacienda_details'),
                $this->respuesta_hacienda
            ),
            'xml_contenido' => $this->when(
                $request->user()?->can('view_hacienda_xml'),
                $this->xml_contenido
            ),
            'metadatos' => $this->when(
                $request->user()?->can('view_hacienda_metadata'),
                $this->metadatos
            ),
            'es_final' => $this->isFinal(),
            'listo_para_envio' => $this->isReadyForSending(),
            'timestamps' => [
                'created_at' => $this->created_at->toIso8601String(),
                'updated_at' => $this->updated_at->toIso8601String(),
            ],
            'links' => [
                'self' => route('api.hacienda.show', $this->id),
                'generar_xml' => route('api.hacienda.generar-xml', $this->id),
                'firmar' => route('api.hacienda.firmar', $this->id),
                'enviar' => route('api.hacienda.enviar', $this->id),
                'estado' => route('api.hacienda.estado', $this->id),
            ],
        ];
    }
}
