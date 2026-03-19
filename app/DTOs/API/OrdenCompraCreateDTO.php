<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class OrdenCompraCreateDTO
{
    /**
     * @param array<mixed> $detalles
     */
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $proveedor_id,
        public readonly int $usuario_id,
        public readonly string $fecha_orden,
        public readonly string $estado,
        public readonly ?string $fecha_entrega_esperada = null,
        public readonly ?string $observaciones = null,
        public readonly array $detalles = [],
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: (int) $request->input('empresa_id'),
            proveedor_id: (int) $request->input('proveedor_id'),
            usuario_id: (int) ($request->input('usuario_id') ?? auth()->id()),
            fecha_orden: (string) $request->input('fecha_orden'),
            estado: (string) $request->input('estado', 'borrador'),
            fecha_entrega_esperada: $request->input('fecha_entrega_esperada'),
            observaciones: $request->filled('observaciones') ? trim((string) $request->input('observaciones')) : null,
            detalles: $request->input('detalles', []),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'proveedor_id' => $this->proveedor_id,
            'usuario_id' => $this->usuario_id,
            'fecha_orden' => $this->fecha_orden,
            'fecha_entrega_esperada' => $this->fecha_entrega_esperada,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
        ];
    }

    /** @return array<mixed> */
    public function getDetalles(): array
    {
        return $this->detalles;
    }
}
