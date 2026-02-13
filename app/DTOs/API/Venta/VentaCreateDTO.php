<?php

namespace App\DTOs\API\Venta;

use Illuminate\Http\Request;

/**
 * DTO para creación de ventas
 *
 * Encapsula toda la lógica de validación y estructuración
 * de datos para crear una nueva venta con sus detalles
 */
final class VentaCreateDTO
{
    /**
     * @param array<DetalleVentaDTO> $detalles
     */
    private function __construct(
        public readonly int $cliente_id,
        public readonly array $detalles, // array de DetalleVentaDTO
        public readonly ?string $observaciones = null,
        public readonly ?float $descuento_adicional = 0,
        public readonly bool $generar_comprobante = false,
    ) {}

    /**
     * Factory desde Request
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public static function fromRequest(Request $request): self
    {
        $detalles = array_map(
            fn(array $detalle) => DetalleVentaDTO::fromArray($detalle),
            $request->input('detalles', [])
        );

        return new self(
            cliente_id: (int) $request->input('cliente_id'),
            detalles: $detalles,
            observaciones: $request->string('observaciones')?->toString() ?? null,
            descuento_adicional: $request->has('descuento_adicional')
                ? (float) $request->input('descuento_adicional')
                : 0,
            generar_comprobante: (bool) $request->input('generar_comprobante', false),
        );
    }

    /**
     * Convertir a array para crear modelo
     */
    public function toModelData(): array
    {
        return [
            'cliente_id' => $this->cliente_id,
            'observaciones' => $this->observaciones,
            'descuento_adicional' => $this->descuento_adicional,
        ];
    }

    /**
     * Obtener datos de detalles para guardar
     */
    public function getDetallesData(): array
    {
        return array_map(
            fn(DetalleVentaDTO $detalle) => $detalle->toArray(),
            $this->detalles
        );
    }

    /**
     * Calcular totales de la venta
     */
    public function calcularTotales(): array
    {
        $subtotal = array_reduce(
            $this->detalles,
            fn($carry, DetalleVentaDTO $detalle) => $carry + $detalle->subtotal(),
            0
        );

        $descuento = $subtotal * (($this->descuento_adicional ?? 0) / 100);
        $subtotal_final = $subtotal - $descuento;
        $impuesto = $subtotal_final * 0.13; // 13% IVA Costa Rica
        $total = $subtotal_final + $impuesto;

        return [
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'subtotal_final' => $subtotal_final,
            'impuesto' => $impuesto,
            'total' => $total,
        ];
    }

    /**
     * Reglas de validación
     */
    public static function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.precio_unitario' => ['required', 'numeric', 'min:0.01'],
            'detalles.*.descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'descuento_adicional' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'generar_comprobante' => ['boolean'],
        ];
    }

    /**
     * Mensajes de validación personalizados
     */
    public static function messages(): array
    {
        return [
            'cliente_id.required' => 'El cliente es requerido.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'detalles.required' => 'Debe agregar al menos un detalle a la venta.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
        ];
    }
}
