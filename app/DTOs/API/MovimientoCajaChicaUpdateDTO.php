<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class MovimientoCajaChicaUpdateDTO
{
    public function __construct(
        public readonly ?int $caja_chica_id = null,
        public readonly ?string $fecha_movimiento = null,
        public readonly ?string $tipo_movimiento = null,
        public readonly ?float $monto = null,
        public readonly ?string $concepto = null,
        public readonly ?string $numero_comprobante = null,
        public readonly ?int $cuenta_contable_id = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            caja_chica_id: $request->filled('caja_chica_id') ? (int) $request->input('caja_chica_id') : null,
            fecha_movimiento: $request->filled('fecha_movimiento') ? (string) $request->input('fecha_movimiento') : null,
            tipo_movimiento: $request->filled('tipo_movimiento') ? (string) $request->input('tipo_movimiento') : null,
            monto: $request->filled('monto') ? (float) $request->input('monto') : null,
            concepto: $request->filled('concepto') ? $request->string('concepto')->trim()->toString() : null,
            numero_comprobante: $request->filled('numero_comprobante') ? $request->string('numero_comprobante')->trim()->toString() : null,
            cuenta_contable_id: $request->filled('cuenta_contable_id') ? (int) $request->input('cuenta_contable_id') : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'caja_chica_id' => $this->caja_chica_id,
            'fecha_movimiento' => $this->fecha_movimiento,
            'tipo_movimiento' => $this->tipo_movimiento,
            'monto' => $this->monto,
            'concepto' => $this->concepto,
            'numero_comprobante' => $this->numero_comprobante,
            'cuenta_contable_id' => $this->cuenta_contable_id,
        ], fn ($value) => $value !== null);
    }
}
