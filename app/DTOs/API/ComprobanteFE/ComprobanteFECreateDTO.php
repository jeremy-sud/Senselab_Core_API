<?php

namespace App\DTOs\API\ComprobanteFE;

use Illuminate\Http\Request;

/**
 * ComprobanteFECreateDTO - DTO para crear Comprobantes Electrónicos
 * 
 * Integra con sistema Hacienda (DGT-R-000-2024).
 * Valida estructura de comprobante electrónico con detalles.
 */
final class ComprobanteFECreateDTO
{
    private function __construct(
        public readonly int $venta_id,
        public readonly string $tipo_comprobante, // FE, TE, NC, ND
        public readonly array $detalles,
        public readonly string $codigo_moneda,
        public readonly float $subtotal,
        public readonly float $impuesto,
        public readonly float $descuento_total,
        public readonly float $total,
        public readonly ?string $observaciones = null,
        public readonly ?string $numero_resolucion = null,
        public readonly ?int $clave = null,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        $detalles = $request->input('detalles', []);
        
        $subtotal = 0;
        $impuesto = 0;
        $descuento_total = 0;

        foreach ($detalles as $detalle) {
            $subtotal += ($detalle['cantidad'] ?? 1) * ($detalle['precio_unitario'] ?? 0);
            $impuesto += ($detalle['impuesto'] ?? 0);
            $descuento_total += ($detalle['descuento'] ?? 0);
        }

        $total = $subtotal + $impuesto - $descuento_total;

        return new self(
            venta_id: (int) $request->input('venta_id'),
            tipo_comprobante: $request->input('tipo_comprobante', 'FE'),
            detalles: $detalles,
            codigo_moneda: $request->input('codigo_moneda', 'CRC'),
            subtotal: $subtotal,
            impuesto: $impuesto,
            descuento_total: $descuento_total,
            total: round($total, 2),
            observaciones: $request->input('observaciones'),
            numero_resolucion: $request->input('numero_resolucion'),
            clave: $request->input('clave') !== null ? (int) $request->input('clave') : null,
        );
    }

    /**
     * Convierte a array para crear modelo
     */
    public function toModelData(): array
    {
        return [
            'venta_id' => $this->venta_id,
            'tipo_comprobante' => $this->tipo_comprobante,
            'codigo_moneda' => $this->codigo_moneda,
            'subtotal' => $this->subtotal,
            'impuesto' => $this->impuesto,
            'descuento_total' => $this->descuento_total,
            'total' => $this->total,
            'detalles' => json_encode($this->detalles),
            'observaciones' => $this->observaciones,
            'numero_resolucion' => $this->numero_resolucion,
            'clave' => $this->clave,
        ];
    }

    /**
     * Reglas de validación Hacienda compliant
     */
    public static function rules(): array
    {
        return [
            'venta_id' => 'required|integer|exists:ventas,id',
            'tipo_comprobante' => 'required|in:FE,TE,NC,ND',
            'codigo_moneda' => 'required|in:CRC,USD',
            'detalles' => 'required|array|min:1',
            'detalles.*.cantidad' => 'required|numeric|min:0.01',
            'detalles.*.precio_unitario' => 'required|numeric|min:0',
            'detalles.*.descripcion' => 'required|string|max:500',
            'detalles.*.impuesto' => 'nullable|numeric|min:0',
            'detalles.*.codigo_impuesto' => 'nullable|in:01,02,03,04',
            'numero_resolucion' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:500',
        ];
    }

    public static function messages(): array
    {
        return [
            'tipo_comprobante.in' => 'Tipo de comprobante inválido. Usar: FE, TE, NC, ND',
            'codigo_moneda.in' => 'Moneda debe ser CRC o USD',
            'detalles.required' => 'Al menos un detalle es requerido',
            'detalles.*.cantidad.numeric' => 'La cantidad debe ser un número',
        ];
    }
}
