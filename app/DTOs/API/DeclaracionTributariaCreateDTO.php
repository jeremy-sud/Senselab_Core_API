<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class DeclaracionTributariaCreateDTO
{
    public function __construct(
        public readonly string $tipo_declaracion,
        public readonly string $periodo_fiscal,
        public readonly string $fecha_inicio_periodo,
        public readonly string $fecha_fin_periodo,
        public readonly ?string $fecha_presentacion = null,
        public readonly ?float $monto_base_imponible = null,
        public readonly ?float $monto_impuesto = null,
        public readonly ?float $monto_creditos = null,
        public readonly ?float $monto_debitos = null,
        public readonly ?float $monto_a_pagar = null,
        public readonly ?float $monto_a_favor = null,
        public readonly ?string $numero_confirmacion = null,
        public readonly ?string $archivo_xml = null,
        public readonly ?string $archivo_pdf = null,
        public readonly string $estado = 'borrador',
        public readonly ?string $notas = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            tipo_declaracion: $request->string('tipo_declaracion')->trim()->toString(),
            periodo_fiscal: $request->string('periodo_fiscal')->trim()->toString(),
            fecha_inicio_periodo: $request->string('fecha_inicio_periodo')->trim()->toString(),
            fecha_fin_periodo: $request->string('fecha_fin_periodo')->trim()->toString(),
            fecha_presentacion: $request->filled('fecha_presentacion') ? $request->string('fecha_presentacion')->trim()->toString() : null,
            monto_base_imponible: $request->filled('monto_base_imponible') ? (float) $request->input('monto_base_imponible') : null,
            monto_impuesto: $request->filled('monto_impuesto') ? (float) $request->input('monto_impuesto') : null,
            monto_creditos: $request->filled('monto_creditos') ? (float) $request->input('monto_creditos') : null,
            monto_debitos: $request->filled('monto_debitos') ? (float) $request->input('monto_debitos') : null,
            monto_a_pagar: $request->filled('monto_a_pagar') ? (float) $request->input('monto_a_pagar') : null,
            monto_a_favor: $request->filled('monto_a_favor') ? (float) $request->input('monto_a_favor') : null,
            numero_confirmacion: $request->filled('numero_confirmacion') ? $request->string('numero_confirmacion')->trim()->toString() : null,
            archivo_xml: $request->filled('archivo_xml') ? $request->string('archivo_xml')->trim()->toString() : null,
            archivo_pdf: $request->filled('archivo_pdf') ? $request->string('archivo_pdf')->trim()->toString() : null,
            estado: $request->filled('estado') ? $request->string('estado')->trim()->toString() : 'borrador',
            notas: $request->filled('notas') ? $request->string('notas')->trim()->toString() : null,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tipo_declaracion' => $this->tipo_declaracion,
            'periodo_fiscal' => $this->periodo_fiscal,
            'fecha_inicio_periodo' => $this->fecha_inicio_periodo,
            'fecha_fin_periodo' => $this->fecha_fin_periodo,
            'fecha_presentacion' => $this->fecha_presentacion,
            'monto_base_imponible' => $this->monto_base_imponible,
            'monto_impuesto' => $this->monto_impuesto,
            'monto_creditos' => $this->monto_creditos,
            'monto_debitos' => $this->monto_debitos,
            'monto_a_pagar' => $this->monto_a_pagar,
            'monto_a_favor' => $this->monto_a_favor,
            'numero_confirmacion' => $this->numero_confirmacion,
            'archivo_xml' => $this->archivo_xml,
            'archivo_pdf' => $this->archivo_pdf,
            'estado' => $this->estado,
            'notas' => $this->notas,
        ];
    }
}
