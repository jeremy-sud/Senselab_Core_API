<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class MensajeHaciendaCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $comprobante_id,
        public readonly string $clave_numerica,
        public readonly string $tipo_mensaje,
        public readonly ?string $codigo_respuesta = null,
        public readonly ?string $detalle_mensaje = null,
        public readonly ?string $xml_respuesta = null,
        public readonly ?string $fecha_emision = null,
        public readonly ?string $fecha_procesamiento = null,
        public readonly string $estado = 'pendiente',
        public readonly int $intentos_envio = 0,
        public readonly ?string $ultimo_error = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: (int) $request->input('empresa_id'),
            comprobante_id: (int) $request->input('comprobante_id'),
            clave_numerica: $request->string('clave_numerica')->trim()->toString(),
            tipo_mensaje: $request->string('tipo_mensaje')->trim()->toString(),
            codigo_respuesta: $request->filled('codigo_respuesta') ? $request->string('codigo_respuesta')->trim()->toString() : null,
            detalle_mensaje: $request->filled('detalle_mensaje') ? $request->string('detalle_mensaje')->trim()->toString() : null,
            xml_respuesta: $request->filled('xml_respuesta') ? $request->string('xml_respuesta')->toString() : null,
            fecha_emision: $request->filled('fecha_emision') ? $request->string('fecha_emision')->trim()->toString() : null,
            fecha_procesamiento: $request->filled('fecha_procesamiento') ? $request->string('fecha_procesamiento')->trim()->toString() : null,
            estado: $request->string('estado', 'pendiente')->trim()->toString(),
            intentos_envio: (int) $request->input('intentos_envio', 0),
            ultimo_error: $request->filled('ultimo_error') ? $request->string('ultimo_error')->trim()->toString() : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'comprobante_id' => $this->comprobante_id,
            'clave_numerica' => $this->clave_numerica,
            'tipo_mensaje' => $this->tipo_mensaje,
            'codigo_respuesta' => $this->codigo_respuesta,
            'detalle_mensaje' => $this->detalle_mensaje,
            'xml_respuesta' => $this->xml_respuesta,
            'fecha_emision' => $this->fecha_emision,
            'fecha_procesamiento' => $this->fecha_procesamiento,
            'estado' => $this->estado,
            'intentos_envio' => $this->intentos_envio,
            'ultimo_error' => $this->ultimo_error,
        ];
    }
}
