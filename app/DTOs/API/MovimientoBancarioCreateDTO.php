<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class MovimientoBancarioCreateDTO
{
    public function __construct(
        public readonly int $cuenta_bancaria_id,
        public readonly string $fecha_movimiento,
        public readonly string $tipo_movimiento,
        public readonly string $descripcion,
        public readonly float $monto,
        public readonly ?string $fecha_valor = null,
        public readonly ?string $numero_referencia = null,
        public readonly ?float $saldo_despues = null,
        public readonly ?string $beneficiario = null,
        public readonly bool $conciliado = false,
        public readonly ?string $fecha_conciliacion = null,
        public readonly ?int $asiento_contable_id = null,
        public readonly ?string $notas = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            cuenta_bancaria_id: (int) $request->input('cuenta_bancaria_id'),
            fecha_movimiento: $request->string('fecha_movimiento')->trim()->toString(),
            tipo_movimiento: $request->string('tipo_movimiento')->trim()->toString(),
            descripcion: $request->string('descripcion')->trim()->toString(),
            monto: (float) $request->input('monto'),
            fecha_valor: $request->filled('fecha_valor') ? $request->string('fecha_valor')->trim()->toString() : null,
            numero_referencia: $request->filled('numero_referencia') ? $request->string('numero_referencia')->trim()->toString() : null,
            saldo_despues: $request->filled('saldo_despues') ? (float) $request->input('saldo_despues') : null,
            beneficiario: $request->filled('beneficiario') ? $request->string('beneficiario')->trim()->toString() : null,
            conciliado: $request->boolean('conciliado', false),
            fecha_conciliacion: $request->filled('fecha_conciliacion') ? $request->string('fecha_conciliacion')->trim()->toString() : null,
            asiento_contable_id: $request->filled('asiento_contable_id') ? (int) $request->input('asiento_contable_id') : null,
            notas: $request->filled('notas') ? $request->string('notas')->trim()->toString() : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'cuenta_bancaria_id' => $this->cuenta_bancaria_id,
            'fecha_movimiento' => $this->fecha_movimiento,
            'tipo_movimiento' => $this->tipo_movimiento,
            'descripcion' => $this->descripcion,
            'monto' => $this->monto,
            'fecha_valor' => $this->fecha_valor,
            'numero_referencia' => $this->numero_referencia,
            'saldo_despues' => $this->saldo_despues,
            'beneficiario' => $this->beneficiario,
            'conciliado' => $this->conciliado,
            'fecha_conciliacion' => $this->fecha_conciliacion,
            'asiento_contable_id' => $this->asiento_contable_id,
            'notas' => $this->notas,
        ];
    }
}
