<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de comprobante electrónico
 *
 * Valida y transforma datos de entrada para la creación de comprobantes electrónicos
 * Fecha de creación: 12 de febrero de 2026
 */
final class ComprobanteElectronicoCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $venta_id,
        public readonly string $tipo_comprobante,
        public readonly string $clave_numerica,
        public readonly \DateTime $fecha_emision,
        public readonly string $estado,
        public readonly ?string $numero_telefax = null,
        public readonly ?string $correo_receptor = null,
        public readonly ?array $detalles_json = null,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: $request->integer('empresa_id'),
            venta_id: $request->integer('venta_id'),
            tipo_comprobante: $request->string('tipo_comprobante'),
            clave_numerica: $request->string('clave_numerica')->trim(),
            fecha_emision: new \DateTime($request->string('fecha_emision')),
            estado: $request->string('estado'),
            numero_telefax: $request->string('numero_telefax')?->trim(),
            correo_receptor: $request->string('correo_receptor')?->trim(),
            detalles_json: $request->array('detalles_json'),
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'venta_id' => $this->venta_id,
            'tipo_comprobante' => $this->tipo_comprobante,
            'clave_numerica' => $this->clave_numerica,
            'fecha_emision' => $this->fecha_emision->format('Y-m-d H:i:s'),
            'estado' => $this->estado,
            'numero_telefax' => $this->numero_telefax,
            'correo_receptor' => $this->correo_receptor,
            'detalles_json' => $this->detalles_json ? json_encode($this->detalles_json) : null,
        ];
    }
}
