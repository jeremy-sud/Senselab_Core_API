<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class CuentaBancariaCreateDTO
{
    public function __construct(
        public readonly string $banco,
        public readonly string $numero_cuenta,
        public readonly string $tipo_cuenta,
        public readonly string $moneda,
        public readonly ?string $iban = null,
        public readonly ?float $saldo_actual = null,
        public readonly ?int $cuenta_contable_id = null,
        public readonly ?string $sucursal_banco = null,
        public readonly ?string $contacto_ejecutivo = null,
        public readonly ?string $telefono_ejecutivo = null,
        public readonly bool $activa = true,
        public readonly bool $es_principal = false,
        public readonly ?string $notas = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            banco: $request->string('banco')->trim()->toString(),
            numero_cuenta: $request->string('numero_cuenta')->trim()->toString(),
            tipo_cuenta: $request->string('tipo_cuenta')->trim()->toString(),
            moneda: $request->string('moneda')->trim()->toString(),
            iban: $request->filled('iban') ? $request->string('iban')->trim()->toString() : null,
            saldo_actual: $request->filled('saldo_actual') ? (float) $request->input('saldo_actual') : null,
            cuenta_contable_id: $request->filled('cuenta_contable_id') ? (int) $request->input('cuenta_contable_id') : null,
            sucursal_banco: $request->filled('sucursal_banco') ? $request->string('sucursal_banco')->trim()->toString() : null,
            contacto_ejecutivo: $request->filled('contacto_ejecutivo') ? $request->string('contacto_ejecutivo')->trim()->toString() : null,
            telefono_ejecutivo: $request->filled('telefono_ejecutivo') ? $request->string('telefono_ejecutivo')->trim()->toString() : null,
            activa: $request->boolean('activa', true),
            es_principal: $request->boolean('es_principal', false),
            notas: $request->filled('notas') ? $request->string('notas')->trim()->toString() : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'banco' => $this->banco,
            'numero_cuenta' => $this->numero_cuenta,
            'tipo_cuenta' => $this->tipo_cuenta,
            'moneda' => $this->moneda,
            'iban' => $this->iban,
            'saldo_actual' => $this->saldo_actual,
            'cuenta_contable_id' => $this->cuenta_contable_id,
            'sucursal_banco' => $this->sucursal_banco,
            'contacto_ejecutivo' => $this->contacto_ejecutivo,
            'telefono_ejecutivo' => $this->telefono_ejecutivo,
            'activa' => $this->activa,
            'es_principal' => $this->es_principal,
            'notas' => $this->notas,
        ];
    }
}
