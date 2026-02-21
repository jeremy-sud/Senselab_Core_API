<?php

namespace App\DTOs\API\Venta;

use Illuminate\Http\Request;

/**
 * VentaUpdateDTO - DTO para actualizar venta
 *
 * Maneja actualizaciones parciales sin revalidar todas las relaciones.
 */
final class VentaUpdateDTO
{
    /**
     * @param array<mixed> $detalles
     */
    private function __construct(
        public readonly ?int $cliente_id = null,
        public readonly ?array $detalles = null,
        public readonly ?string $observaciones = null,
        public readonly ?float $descuento_adicional = null,
        public readonly ?bool $generar_comprobante = null,
        public readonly ?string $estado = null,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            cliente_id: $request->input('cliente_id') !== null ? (int) $request->input('cliente_id') : null,
            detalles: $request->input('detalles'),
            observaciones: $request->input('observaciones'),
            descuento_adicional: $request->input('descuento_adicional') !== null
                ? (float) $request->input('descuento_adicional')
                : null,
            generar_comprobante: $request->input('generar_comprobante') !== null
                ? (bool) $request->input('generar_comprobante')
                : null,
            estado: $request->input('estado'),
        );
    }

    /**
     * Convierte a array para actualizar modelo
     */
    /**
     * Convert the DTO to model data array.
     *
     * @return array<string, mixed>
     */
    public function toModelData(): array
    {
        $data = [];

        if ($this->cliente_id !== null) {
            $data['cliente_id'] = $this->cliente_id;
        }
        if ($this->observaciones !== null) {
            $data['observaciones'] = $this->observaciones;
        }
        if ($this->descuento_adicional !== null) {
            $data['descuento_adicional'] = $this->descuento_adicional;
        }
        if ($this->generar_comprobante !== null) {
            $data['generar_comprobante'] = $this->generar_comprobante;
        }
        if ($this->estado !== null) {
            $data['estado'] = $this->estado;
        }
        if ($this->detalles !== null) {
            $data['detalles'] = json_encode($this->detalles);
        }

        return $data;
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
            'cliente_id' => 'sometimes|required|integer|exists:clientes,id',
            'detalles' => 'sometimes|required|array|min:1',
            'detalles.*.producto_id' => 'required_with:detalles|integer|exists:productos,id',
            'detalles.*.cantidad' => 'required_with:detalles|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required_with:detalles|numeric|min:0',
            'observaciones' => 'sometimes|nullable|string|max:500',
            'descuento_adicional' => 'sometimes|nullable|numeric|min:0|max:100',
            'generar_comprobante' => 'sometimes|boolean',
            'estado' => 'sometimes|in:borrador,pendiente,completada,cancelada',
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
            'cliente_id.exists' => 'El cliente no existe',
            'estado.in' => 'Estado inválido',
        ];
    }
}
