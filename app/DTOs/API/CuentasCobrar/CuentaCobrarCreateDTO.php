<?php

namespace App\DTOs\API\CuentasCobrar;

use Illuminate\Http\Request;

/**
 * CuentaCobrarCreateDTO - DTO para crear cuentas por cobrar
 *
 * Registra créditos otorgados a clientes con plazo de pago.
 */
final class CuentaCobrarCreateDTO
{
    /**
     * @param array<mixed> $cuotas
     */
    private function __construct(
        public readonly int $cliente_id,
        public readonly float $monto,
        public readonly \DateTime $fecha_emision,
        public readonly \DateTime $fecha_vencimiento,
        public readonly string $tipo_documento, // factura, nota, cheque
        public readonly ?string $numero_documento = null,
        public readonly ?string $observaciones = null,
        public readonly ?array $cuotas = null,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            cliente_id: (int) $request->input('cliente_id'),
            monto: (float) $request->input('monto'),
            fecha_emision: new \DateTime($request->input('fecha_emision')),
            fecha_vencimiento: new \DateTime($request->input('fecha_vencimiento')),
            tipo_documento: $request->input('tipo_documento', 'factura'),
            numero_documento: $request->input('numero_documento'),
            observaciones: $request->input('observaciones'),
            cuotas: $request->input('cuotas'),
        );
    }

    /**
     * Calcula días de vencimiento
     */
    public function diasVencimiento(): int
    {
        $hoy = new \DateTime();
        return (int) $this->fecha_vencimiento->diff($hoy)->days;
    }

    /**
     * Verifica si está vencida
     */
    public function estaVencida(): bool
    {
        return $this->diasVencimiento() > 0;
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
            'cliente_id' => $this->cliente_id,
            'monto' => $this->monto,
            'fecha_emision' => $this->fecha_emision->format('Y-m-d'),
            'fecha_vencimiento' => $this->fecha_vencimiento->format('Y-m-d'),
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'observaciones' => $this->observaciones,
            'cuotas' => $this->cuotas ? json_encode($this->cuotas) : null,
            'estado' => 'pendiente',
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
            'cliente_id' => 'required|integer|exists:clientes,id',
            'monto' => 'required|numeric|min:0.01',
            'fecha_emision' => 'required|date',
            'fecha_vencimiento' => 'required|date|after:fecha_emision',
            'tipo_documento' => 'required|in:factura,nota,cheque',
            'numero_documento' => 'nullable|string|max:50',
            'observaciones' => 'nullable|string|max:500',
            'cuotas' => 'nullable|array',
            'cuotas.*.numero_cuota' => 'required_with:cuotas|integer|min:1',
            'cuotas.*.monto_cuota' => 'required_with:cuotas|numeric|min:0.01',
            'cuotas.*.fecha_vencimiento' => 'required_with:cuotas|date',
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
            'monto.min' => 'El monto debe ser mayor a 0',
            'fecha_vencimiento.after' => 'La fecha de vencimiento debe ser posterior a la emisión',
            'tipo_documento.in' => 'Tipo de documento inválido',
        ];
    }
}
