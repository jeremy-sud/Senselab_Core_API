<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class DetalleEntradaInventarioCreateDTO
{
    public function __construct(
        public readonly int $entrada_inventario_id,
        public readonly int $producto_id,
        public readonly int $numero_linea,
        public readonly float $cantidad,
        public readonly float $costo_unitario,
        public readonly float $subtotal,
        public readonly float $total_linea,
        public readonly ?float $porcentaje_impuesto = null,
        public readonly ?float $monto_impuesto = null,
        public readonly ?string $lote = null,
        public readonly ?string $fecha_vencimiento = null,
        public readonly ?string $observaciones = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            entrada_inventario_id: (int) $request->input('entrada_inventario_id'),
            producto_id: (int) $request->input('producto_id'),
            numero_linea: (int) $request->input('numero_linea'),
            cantidad: (float) $request->input('cantidad'),
            costo_unitario: (float) $request->input('costo_unitario'),
            subtotal: (float) $request->input('subtotal'),
            total_linea: (float) $request->input('total_linea'),
            porcentaje_impuesto: $request->filled('porcentaje_impuesto') ? (float) $request->input('porcentaje_impuesto') : null,
            monto_impuesto: $request->filled('monto_impuesto') ? (float) $request->input('monto_impuesto') : null,
            lote: $request->filled('lote') ? $request->string('lote')->trim()->toString() : null,
            fecha_vencimiento: $request->filled('fecha_vencimiento') ? $request->string('fecha_vencimiento')->trim()->toString() : null,
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'entrada_inventario_id' => $this->entrada_inventario_id,
            'producto_id' => $this->producto_id,
            'numero_linea' => $this->numero_linea,
            'cantidad' => $this->cantidad,
            'costo_unitario' => $this->costo_unitario,
            'subtotal' => $this->subtotal,
            'total_linea' => $this->total_linea,
            'porcentaje_impuesto' => $this->porcentaje_impuesto,
            'monto_impuesto' => $this->monto_impuesto,
            'lote' => $this->lote,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'observaciones' => $this->observaciones,
        ];
    }
}
