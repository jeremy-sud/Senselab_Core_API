<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de ventas
 * 
 * Valida y transforma datos de entrada para la creación de ventas
 * Fecha de creación: 12 de febrero de 2026
 */
final class VentaCreateDTO
{
    public function __construct(
        public readonly int $cliente_id,
        public readonly int $empresa_id,
        public readonly \DateTime $fecha,
        public readonly float $subtotal,
        public readonly float $impuesto,
        public readonly float $total,
        public readonly string $estado,
        public readonly ?string $numero_comprobante = null,
        public readonly ?string $observaciones = null,
        public readonly ?int $forma_pago_id = null,
        public readonly array $detalles = [],
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            cliente_id: $request->integer('cliente_id'),
            empresa_id: $request->integer('empresa_id'),
            fecha: new \DateTime($request->string('fecha')),
            subtotal: $request->float('subtotal'),
            impuesto: $request->float('impuesto'),
            total: $request->float('total'),
            estado: $request->string('estado'),
            numero_comprobante: $request->string('numero_comprobante')?->trim(),
            observaciones: $request->string('observaciones')?->trim(),
            forma_pago_id: $request->integer('forma_pago_id'),
            detalles: $request->array('detalles', []),
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    public function toArray(): array
    {
        return [
            'cliente_id' => $this->cliente_id,
            'empresa_id' => $this->empresa_id,
            'fecha' => $this->fecha->format('Y-m-d'),
            'subtotal' => $this->subtotal,
            'impuesto' => $this->impuesto,
            'total' => $this->total,
            'estado' => $this->estado,
            'numero_comprobante' => $this->numero_comprobante,
            'observaciones' => $this->observaciones,
            'forma_pago_id' => $this->forma_pago_id,
        ];
    }

    /**
     * Obtener detalles de la venta
     */
    public function getDetalles(): array
    {
        return $this->detalles;
    }
}
