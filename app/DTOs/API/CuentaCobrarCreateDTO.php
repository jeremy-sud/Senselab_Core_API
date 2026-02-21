<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de cuenta por cobrar
 *
 * Valida y transforma datos de entrada para la creación de cuentas por cobrar
 * Fecha de creación: 12 de febrero de 2026
 */
final class CuentaCobrarCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $cliente_id,
        public readonly int $venta_id,
        public readonly float $monto_total,
        public readonly float $monto_pagado,
        public readonly \DateTime $fecha_vencimiento,
        public readonly string $estado,
        public readonly ?string $observaciones = null,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: $request->integer('empresa_id'),
            cliente_id: $request->integer('cliente_id'),
            venta_id: $request->integer('venta_id'),
            monto_total: $request->float('monto_total'),
            monto_pagado: $request->float('monto_pagado', 0),
            fecha_vencimiento: new \DateTime($request->string('fecha_vencimiento')),
            estado: $request->string('estado'),
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'cliente_id' => $this->cliente_id,
            'venta_id' => $this->venta_id,
            'monto_total' => $this->monto_total,
            'monto_pagado' => $this->monto_pagado,
            'fecha_vencimiento' => $this->fecha_vencimiento->format('Y-m-d'),
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
        ];
    }
}
