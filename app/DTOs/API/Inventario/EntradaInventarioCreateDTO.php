<?php

namespace App\DTOs\API\Inventario;

use Illuminate\Http\Request;

/**
 * EntradaInventarioCreateDTO - DTO para registrar entrada de producto a inventario
 *
 * Maneja compras, devoluciones recibidas y ajustes positivos de inventario.
 */
final class EntradaInventarioCreateDTO
{
    /**
     * @param array<mixed> $lotes
     */
    private function __construct(
        public readonly int $producto_id,
        public readonly int $proveedor_id,
        public readonly float $cantidad,
        public readonly float $precio_unitario,
        public readonly string $tipo_movimiento, // compra, devolucion, ajuste
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
            proveedor_id: (int) $request->input('proveedor_id'),
            cantidad: (float) $request->input('cantidad'),
            precio_unitario: (float) $request->input('precio_unitario'),
            tipo_movimiento: $request->input('tipo_movimiento', 'compra'),
            numero_documento: $request->input('numero_documento'),
            observaciones: $request->input('observaciones'),
            lotes: $request->input('lotes'),
        );
    }

    /**
     * Costo total de la entrada
     */
    public function costoTotal(): float
    {
        return round($this->cantidad * $this->precio_unitario, 2);
    }

    /**
     * Convierte a array para modelo
     */
    /**
     * Convert the DTO to model data array.
     *
     * @return array<string, mixed>
     */
    public function toModelData(): array
    {
        return [
            'producto_id' => $this->producto_id,
            'proveedor_id' => $this->proveedor_id,
            'cantidad' => $this->cantidad,
            'precio_unitario' => $this->precio_unitario,
            'costo_total' => $this->costoTotal(),
            'tipo_movimiento' => $this->tipo_movimiento,
            'numero_documento' => $this->numero_documento,
            'observaciones' => $this->observaciones,
            'lotes' => $this->lotes ? json_encode($this->lotes) : null,
        ];
    }

    /**
     * Reglas de validación
     */
    /**
     * Get the validation rules.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'producto_id' => 'required|integer|exists:productos,id',
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'cantidad' => 'required|numeric|min:0.01',
            'precio_unitario' => 'required|numeric|min:0',
            'tipo_movimiento' => 'required|in:compra,devolucion,ajuste',
            'numero_documento' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:500',
            'lotes' => 'nullable|array',
            'lotes.*.numero_lote' => 'required_with:lotes|string|max:50',
            'lotes.*.fecha_vencimiento' => 'required_with:lotes|date',
            'lotes.*.cantidad_lote' => 'required_with:lotes|numeric|min:0.01',
        ];
    }

    /**
     * Get the validation messages.
     *
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'producto_id.exists' => 'El producto no existe',
            'proveedor_id.exists' => 'El proveedor no existe',
            'cantidad.numeric' => 'La cantidad debe ser un número',
            'cantidad.min' => 'La cantidad debe ser mayor a 0',
            'tipo_movimiento.in' => 'Tipo de movimiento inválido',
        ];
    }
}
