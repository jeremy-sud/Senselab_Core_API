<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class ComprobanteRecibidoCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly ?int $proveedor_id = null,
        public readonly string $clave_numerica = '',
        public readonly ?string $consecutivo = null,
        public readonly ?string $fecha_emision = null,
        public readonly ?string $tipo_documento = null,
        public readonly ?string $numero_cedula_emisor = null,
        public readonly ?string $nombre_emisor = null,
        public readonly float $monto_total = 0,
        public readonly float $monto_impuesto = 0,
        public readonly string $moneda = 'CRC',
        public readonly ?string $xml_original = null,
        public readonly string $estado_validacion = 'pendiente',
        public readonly ?string $mensaje_hacienda = null,
        public readonly ?string $detalle_mensaje = null,
        public readonly bool $contabilizado = false,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: (int) $request->input('empresa_id'),
            proveedor_id: $request->filled('proveedor_id') ? (int) $request->input('proveedor_id') : null,
            clave_numerica: $request->string('clave_numerica')->trim()->toString(),
            consecutivo: $request->filled('consecutivo') ? $request->string('consecutivo')->trim()->toString() : null,
            fecha_emision: $request->filled('fecha_emision') ? $request->string('fecha_emision')->trim()->toString() : null,
            tipo_documento: $request->filled('tipo_documento') ? $request->string('tipo_documento')->trim()->toString() : null,
            numero_cedula_emisor: $request->filled('numero_cedula_emisor') ? $request->string('numero_cedula_emisor')->trim()->toString() : null,
            nombre_emisor: $request->filled('nombre_emisor') ? $request->string('nombre_emisor')->trim()->toString() : null,
            monto_total: (float) $request->input('monto_total', 0),
            monto_impuesto: (float) $request->input('monto_impuesto', 0),
            moneda: $request->string('moneda', 'CRC')->trim()->toString(),
            xml_original: $request->filled('xml_original') ? $request->string('xml_original')->toString() : null,
            estado_validacion: $request->string('estado_validacion', 'pendiente')->trim()->toString(),
            mensaje_hacienda: $request->filled('mensaje_hacienda') ? $request->string('mensaje_hacienda')->trim()->toString() : null,
            detalle_mensaje: $request->filled('detalle_mensaje') ? $request->string('detalle_mensaje')->trim()->toString() : null,
            contabilizado: $request->boolean('contabilizado', false),
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'proveedor_id' => $this->proveedor_id,
            'clave_numerica' => $this->clave_numerica,
            'consecutivo' => $this->consecutivo,
            'fecha_emision' => $this->fecha_emision,
            'tipo_documento' => $this->tipo_documento,
            'numero_cedula_emisor' => $this->numero_cedula_emisor,
            'nombre_emisor' => $this->nombre_emisor,
            'monto_total' => $this->monto_total,
            'monto_impuesto' => $this->monto_impuesto,
            'moneda' => $this->moneda,
            'xml_original' => $this->xml_original,
            'estado_validacion' => $this->estado_validacion,
            'mensaje_hacienda' => $this->mensaje_hacienda,
            'detalle_mensaje' => $this->detalle_mensaje,
            'contabilizado' => $this->contabilizado,
            'activo' => $this->activo,
        ];
    }
}
