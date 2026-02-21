<?php

namespace App\DTOs\API\Pagos;

use Illuminate\Http\Request;

/**
 * PagoCreateDTO - DTO para registrar pagos a clientes/proveedores
 *
 * Soporta múltiples formas de pago: efectivo, cheque, transferencia, tarjeta.
 */
final class PagoCreateDTO
{
    /**
     * @param array<mixed> $cuentas_cobrar_pagadas
     */
    private function __construct(
        public readonly int $entidad_id, // cliente_id o proveedor_id
        public readonly string $tipo_entidad, // cliente, proveedor
        public readonly float $monto,
        public readonly string $forma_pago, // efectivo, cheque, transferencia, tarjeta
        public readonly \DateTime $fecha_pago,
        public readonly ?string $numero_transaccion = null,
        public readonly ?string $numero_cheque = null,
        public readonly ?string $banco = null,
        public readonly ?string $numero_cuenta = null,
        public readonly ?string $referencia = null,
        public readonly ?string $observaciones = null,
        public readonly ?array $cuentas_cobrar_pagadas = null,
    ) {}

    /**
     * Factory method desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            entidad_id: (int) $request->input('entidad_id'),
            tipo_entidad: $request->input('tipo_entidad'),
            monto: (float) $request->input('monto'),
            forma_pago: $request->input('forma_pago'),
            fecha_pago: new \DateTime($request->input('fecha_pago')),
            numero_transaccion: $request->input('numero_transaccion'),
            numero_cheque: $request->input('numero_cheque'),
            banco: $request->input('banco'),
            numero_cuenta: $request->input('numero_cuenta'),
            referencia: $request->input('referencia'),
            observaciones: $request->input('observaciones'),
            cuentas_cobrar_pagadas: $request->input('cuentas_cobrar_pagadas'),
        );
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
            'entidad_id' => $this->entidad_id,
            'tipo_entidad' => $this->tipo_entidad,
            'monto' => $this->monto,
            'forma_pago' => $this->forma_pago,
            'fecha_pago' => $this->fecha_pago->format('Y-m-d H:i:s'),
            'numero_transaccion' => $this->numero_transaccion,
            'numero_cheque' => $this->numero_cheque,
            'banco' => $this->banco,
            'numero_cuenta' => $this->numero_cuenta,
            'referencia' => $this->referencia,
            'observaciones' => $this->observaciones,
            'cuentas_cobrar_pagadas' => $this->cuentas_cobrar_pagadas ? json_encode($this->cuentas_cobrar_pagadas) : null,
            'estado' => 'registrado',
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
            'entidad_id' => 'required|integer',
            'tipo_entidad' => 'required|in:cliente,proveedor',
            'monto' => 'required|numeric|min:0.01',
            'forma_pago' => 'required|in:efectivo,cheque,transferencia,tarjeta',
            'fecha_pago' => 'required|date',
            'numero_transaccion' => 'nullable|string|max:50',
            'numero_cheque' => 'nullable|string|max:50',
            'banco' => 'nullable|string|max:100',
            'numero_cuenta' => 'nullable|string|max:50',
            'referencia' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string|max:500',
            'cuentas_cobrar_pagadas' => 'nullable|array',
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
            'tipo_entidad.in' => 'Tipo de entidad debe ser cliente o proveedor',
            'forma_pago.in' => 'Forma de pago inválida',
            'monto.min' => 'El monto debe ser mayor a 0',
        ];
    }
}
