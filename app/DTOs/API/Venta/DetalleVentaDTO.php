<?php

namespace App\DTOs\API\Venta;

/**
 * DTO para un detalle individual de venta
 * 
 * Representa una línea de venta con producto y cantidad
 */
final class DetalleVentaDTO
{
    public function __construct(
        public readonly int $producto_id,
        public readonly int $cantidad,
        public readonly float $precio_unitario,
        public readonly ?float $descuento_porcentaje = 0,
        public readonly ?string $referencia = null,
    ) {}

    /**
     * Convertir a array para almacenamiento
     */
    public function toArray(): array
    {
        return [
            'producto_id' => $this->producto_id,
            'cantidad' => $this->cantidad,
            'precio_unitario' => $this->precio_unitario,
            'descuento_porcentaje' => $this->descuento_porcentaje ?? 0,
            'referencia' => $this->referencia,
        ];
    }

    /**
     * Calcular subtotal línea
     */
    public function subtotal(): float
    {
        $subtotal = $this->cantidad * $this->precio_unitario;
        $descuento = $subtotal * (($this->descuento_porcentaje ?? 0) / 100);
        return $subtotal - $descuento;
    }

    /**
     * Crear desde array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            producto_id: (int) $data['producto_id'],
            cantidad: (int) $data['cantidad'],
            precio_unitario: (float) $data['precio_unitario'],
            descuento_porcentaje: isset($data['descuento_porcentaje'])
                ? (float) $data['descuento_porcentaje']
                : 0,
            referencia: $data['referencia'] ?? null,
        );
    }

    /**
     * Validar reglas para detalle
     */
    public static function rules(): array
    {
        return [
            '*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            '*.cantidad' => ['required', 'integer', 'min:1', 'max:10000'],
            '*.precio_unitario' => ['required', 'numeric', 'min:0.01'],
            '*.descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            '*.referencia' => ['nullable', 'string', 'max:255'],
        ];
    }
}
