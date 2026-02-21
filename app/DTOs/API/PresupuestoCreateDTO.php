<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de presupuesto
 *
 * Valida y transforma datos de entrada para la creación de presupuestos
 * Fecha de creación: 12 de febrero de 2026
 */
final class PresupuestoCreateDTO
{
    /**
     * @param array<mixed> $detalles
     */
    /**
     * @param array<mixed> $detalles
     */
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $cliente_id,
        public readonly \DateTime $fecha,
        public readonly \DateTime $fecha_vencimiento,
        public readonly float $subtotal,
        public readonly float $impuesto,
        public readonly float $total,
        public readonly string $estado,
        public readonly ?string $numero_presupuesto = null,
        public readonly ?string $observaciones = null,
        public readonly array $detalles = [],
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: $request->integer('empresa_id'),
            cliente_id: $request->integer('cliente_id'),
            fecha: new \DateTime($request->string('fecha')),
            fecha_vencimiento: new \DateTime($request->string('fecha_vencimiento')),
            subtotal: $request->float('subtotal'),
            impuesto: $request->float('impuesto'),
            total: $request->float('total'),
            estado: $request->string('estado'),
            numero_presupuesto: $request->filled('numero_presupuesto') ? $request->string('numero_presupuesto')->trim()->toString() : null,
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
            detalles: $request->array('detalles'),
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
            'fecha' => $this->fecha->format('Y-m-d'),
            'fecha_vencimiento' => $this->fecha_vencimiento->format('Y-m-d'),
            'subtotal' => $this->subtotal,
            'impuesto' => $this->impuesto,
            'total' => $this->total,
            'estado' => $this->estado,
            'numero_presupuesto' => $this->numero_presupuesto,
            'observaciones' => $this->observaciones,
        ];
    }

    /**
     * Obtener detalles del presupuesto
     */
    /**
     * @return array<mixed>
     */
    /**
     * @return array<mixed>
     */
    public function getDetalles(): array
    {
        return $this->detalles;
    }
}
