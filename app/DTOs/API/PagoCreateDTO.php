<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de pago
 *
 * Valida y transforma datos de entrada para la creación de pagos
 * Fecha de creación: 12 de febrero de 2026
 */
final class PagoCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly float $monto,
        public readonly string $tipo_pago,
        public readonly string $forma_pago,
        public readonly \DateTime $fecha_pago,
        public readonly ?int $cuenta_bancaria_id = null,
        public readonly ?string $numero_referencia = null,
        public readonly ?string $observaciones = null,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: $request->integer('empresa_id'),
            monto: $request->float('monto'),
            tipo_pago: $request->string('tipo_pago'),
            forma_pago: $request->string('forma_pago'),
            fecha_pago: new \DateTime($request->string('fecha_pago')),
            cuenta_bancaria_id: $request->integer('cuenta_bancaria_id'),
            numero_referencia: $request->filled('numero_referencia') ? $request->string('numero_referencia')->trim()->toString() : null,
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    /**
     * Convert the DTO to an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'monto' => $this->monto,
            'tipo_pago' => $this->tipo_pago,
            'forma_pago' => $this->forma_pago,
            'fecha_pago' => $this->fecha_pago->format('Y-m-d H:i:s'),
            'cuenta_bancaria_id' => $this->cuenta_bancaria_id,
            'numero_referencia' => $this->numero_referencia,
            'observaciones' => $this->observaciones,
        ];
    }
}
