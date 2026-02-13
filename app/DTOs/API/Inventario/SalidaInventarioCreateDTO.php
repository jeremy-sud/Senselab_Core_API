<?php

namespace App\DTOs\API\Inventario;

use Illuminate\Http\Request;

/**
 * SalidaInventarioCreateDTO - DTO para registrar salida de inventario
 * 
 * Maneja ventas, devoluciones otorgadas y ajustes negativos de inventario.
 */
final class SalidaInventarioCreateDTO
{
    private function __construct(
        public readonly int $producto_id,
        public readonly float $cantidad,
        public readonly string $tipo_movimiento, // venta, devolucion, ajuste, merma
        public readonly ?int $venta_id = null,
        public readonly ?int $cliente_id = null,
        public readonly ?string $numero_documento = null,
        public readonly ?string $observaciones = null,
        public readonly ?array $lotes = null,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            producto_id: (int) $request->input('producto_id'),
            cantidad: (float) $request->input('cantidad'),
            tipo_movimiento: $request->input('tipo_movimiento', 'venta'),
            venta_id: $request->input('venta_id') !== null ? (int) $request->input('venta_id') : null,
            cliente_id: $request->input('cliente_id') !== null ? (int) $request->input('cliente_id') : null,
            numero_documento: $request->input('numero_documento'),
            observaciones: $request->input('observaciones'),
            lotes: $request->input('lotes'),
        );
    }

    /**
     * Convierte a array para modelo
     */
    public function toModelData(): array
    {
        return [
            'producto_id' => $this->producto_id,
            'cantidad' => $this->cantidad,
            'tipo_movimiento' => $this->tipo_movimiento,
            'venta_id' => $this->venta_id,
            'cliente_id' => $this->cliente_id,
            'numero_documento' => $this->numero_documento,
            'observaciones' => $this->observaciones,
            'lotes' => $this->lotes ? json_encode($this->lotes) : null,
        ];
    }

    /**
     * Reglas de validación
     */
    public static function rules(): array
    {
        return [
            'producto_id' => 'required|integer|exists:productos,id',
            'cantidad' => 'required|numeric|min:0.01',
            'tipo_movimiento' => 'required|in:venta,devolucion,ajuste,merma',
            'venta_id' => 'nullable|integer|exists:ventas,id',
            'cliente_id' => 'nullable|integer|exists:clientes,id',
            'numero_documento' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:500',
            'lotes' => 'nullable|array',
            'lotes.*.numero_lote' => 'required_with:lotes|string|max:50',
            'lotes.*.cantidad_lote' => 'required_with:lotes|numeric|min:0.01',
        ];
    }

    public static function messages(): array
    {
        return [
            'producto_id.exists' => 'El producto no existe',
            'cantidad.min' => 'La cantidad debe ser mayor a 0',
            'tipo_movimiento.in' => 'Tipo de movimiento inválido. Usar: venta, devolucion, ajuste, merma',
            'venta_id.exists' => 'La venta no existe',
            'cliente_id.exists' => 'El cliente no existe',
        ];
    }
}
