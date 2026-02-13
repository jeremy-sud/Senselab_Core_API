<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de cuenta por pagar
 *
 * Valida y transforma datos de entrada para la creación de cuentas por pagar
 * Fecha de creación: 12 de febrero de 2026
 */
final class CuentaPagarCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $proveedor_id,
        public readonly float $monto_total,
        public readonly float $monto_pagado,
        public readonly \DateTime $fecha_vencimiento,
        public readonly string $estado,
        public readonly ?string $numero_factura = null,
        public readonly ?string $observaciones = null,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: $request->integer('empresa_id'),
            proveedor_id: $request->integer('proveedor_id'),
            monto_total: $request->float('monto_total'),
            monto_pagado: $request->float('monto_pagado', 0),
            fecha_vencimiento: new \DateTime($request->string('fecha_vencimiento')),
            estado: $request->string('estado'),
            numero_factura: $request->string('numero_factura')?->trim(),
            observaciones: $request->string('observaciones')?->trim(),
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'proveedor_id' => $this->proveedor_id,
            'monto_total' => $this->monto_total,
            'monto_pagado' => $this->monto_pagado,
            'fecha_vencimiento' => $this->fecha_vencimiento->format('Y-m-d'),
            'estado' => $this->estado,
            'numero_factura' => $this->numero_factura,
            'observaciones' => $this->observaciones,
        ];
    }
}
