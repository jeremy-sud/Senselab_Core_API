<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class DetalleSalidaInventarioCreateDTO
{
    public function __construct(
        public readonly int $salida_inventario_id,
        public readonly int $producto_id,
        public readonly int $numero_linea,
        public readonly float $cantidad,
        public readonly float $costo_unitario,
        public readonly float $total_linea,
        public readonly ?string $lote = null,
        public readonly ?string $observaciones = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            salida_inventario_id: (int) $request->input('salida_inventario_id'),
            producto_id: (int) $request->input('producto_id'),
            numero_linea: (int) $request->input('numero_linea'),
            cantidad: (float) $request->input('cantidad'),
            costo_unitario: (float) $request->input('costo_unitario'),
            total_linea: (float) $request->input('total_linea'),
            lote: $request->filled('lote') ? $request->string('lote')->trim()->toString() : null,
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'salida_inventario_id' => $this->salida_inventario_id,
            'producto_id' => $this->producto_id,
            'numero_linea' => $this->numero_linea,
            'cantidad' => $this->cantidad,
            'costo_unitario' => $this->costo_unitario,
            'total_linea' => $this->total_linea,
            'lote' => $this->lote,
            'observaciones' => $this->observaciones,
        ];
    }
}
