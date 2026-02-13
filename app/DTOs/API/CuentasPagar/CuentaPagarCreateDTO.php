<?php

namespace App\DTOs\API\CuentasPagar;

use Illuminate\Http\Request;

/**
 * CuentaPagarCreateDTO - DTO para crear cuentas por pagar
 *
 * Registra créditos recibidos de proveedores con plazo de pago.
 */
final class CuentaPagarCreateDTO
{
    private function __construct(
        public readonly int $proveedor_id,
        public readonly float $monto,
        public readonly \DateTime $fecha_emision,
        public readonly \DateTime $fecha_vencimiento,
        public readonly string $tipo_documento, // factura, nota, cheque
        public readonly ?string $numero_documento = null,
        public readonly ?string $numero_referencia_proveedor = null,
        public readonly ?string $observaciones = null,
        public readonly ?array $cuotas = null,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            proveedor_id: (int) $request->input('proveedor_id'),
            monto: (float) $request->input('monto'),
            fecha_emision: new \DateTime($request->input('fecha_emision')),
            fecha_vencimiento: new \DateTime($request->input('fecha_vencimiento')),
            tipo_documento: $request->input('tipo_documento', 'factura'),
            numero_documento: $request->input('numero_documento'),
            numero_referencia_proveedor: $request->input('numero_referencia_proveedor'),
            observaciones: $request->input('observaciones'),
            cuotas: $request->input('cuotas'),
        );
    }

    /**
     * Días hasta vencimiento (positivo = futuro, negativo = vencido)
     */
    public function diasHastaVencimiento(): int
    {
        $hoy = new \DateTime();
        return (int) $hoy->diff($this->fecha_vencimiento)->days;
    }

    /**
     * Verifica si está vencida
     */
    public function estaVencida(): bool
    {
        return $this->diasHastaVencimiento() < 0;
    }

    /**
     * Convierte a array para modelo
     */
    public function toModelData(): array
    {
        return [
            'proveedor_id' => $this->proveedor_id,
            'monto' => $this->monto,
            'fecha_emision' => $this->fecha_emision->format('Y-m-d'),
            'fecha_vencimiento' => $this->fecha_vencimiento->format('Y-m-d'),
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'numero_referencia_proveedor' => $this->numero_referencia_proveedor,
            'observaciones' => $this->observaciones,
            'cuotas' => $this->cuotas ? json_encode($this->cuotas) : null,
            'estado' => 'pendiente',
        ];
    }

    /**
     * Reglas de validación
     */
    public static function rules(): array
    {
        return [
            'proveedor_id' => 'required|integer|exists:proveedores,id',
            'monto' => 'required|numeric|min:0.01',
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'required|date|after:fecha_emision',
            'tipo_documento' => 'required|in:factura,nota,cheque',
            'numero_documento' => 'nullable|string|max:50',
            'numero_referencia_proveedor' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:500',
            'cuotas' => 'nullable|array',
            'cuotas.*.numero_cuota' => 'required_with:cuotas|integer|min:1',
            'cuotas.*.monto_cuota' => 'required_with:cuotas|numeric|min:0.01',
            'cuotas.*.fecha_vencimiento' => 'required_with:cuotas|date',
        ];
    }

    public static function messages(): array
    {
        return [
            'proveedor_id.exists' => 'El proveedor no existe',
            'monto.min' => 'El monto debe ser mayor a 0',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a la emisión',
            'tipo_documento.in' => 'Tipo de documento inválido',
        ];
    }
}
