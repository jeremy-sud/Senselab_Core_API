<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class MovimientoCajaChicaCreateDTO
{
    public function __construct(
        public readonly int $caja_chica_id,
        public readonly string $fecha_movimiento,
        public readonly string $tipo_movimiento,
        public readonly float $monto,
        public readonly string $concepto,
        public readonly ?string $numero_comprobante = null,
        public readonly ?int $cuenta_contable_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            caja_chica_id: (int) $request->input('caja_chica_id'),
            fecha_movimiento: (string) $request->input('fecha_movimiento'),
            tipo_movimiento: (string) $request->input('tipo_movimiento'),
            monto: (float) $request->input('monto'),
            concepto: $request->string('concepto')->trim()->toString(),
            numero_comprobante: $request->filled('numero_comprobante') ? $request->string('numero_comprobante')->trim()->toString() : null,
            cuenta_contable_id: $request->filled('cuenta_contable_id') ? (int) $request->input('cuenta_contable_id') : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'caja_chica_id' => $this->caja_chica_id,
            'fecha_movimiento' => $this->fecha_movimiento,
            'tipo_movimiento' => $this->tipo_movimiento,
            'monto' => $this->monto,
            'concepto' => $this->concepto,
            'numero_comprobante' => $this->numero_comprobante,
            'cuenta_contable_id' => $this->cuenta_contable_id,
        ];
    }
}
