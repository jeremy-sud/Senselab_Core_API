<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class ConsecutivoFeCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly ?int $sucursal_id = null,
        public readonly string $tipo_comprobante = '01',
        public readonly ?string $prefijo = null,
        public readonly int $consecutivo_actual = 0,
        public readonly int $consecutivo_inicial = 1,
        public readonly ?int $consecutivo_final = null,
        public readonly ?string $fecha_resolucion = null,
        public readonly ?string $numero_resolucion = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: (int) $request->input('empresa_id'),
            sucursal_id: $request->filled('sucursal_id') ? (int) $request->input('sucursal_id') : null,
            tipo_comprobante: $request->string('tipo_comprobante', '01')->trim()->toString(),
            prefijo: $request->filled('prefijo') ? $request->string('prefijo')->trim()->toString() : null,
            consecutivo_actual: (int) $request->input('consecutivo_actual', 0),
            consecutivo_inicial: (int) $request->input('consecutivo_inicial', 1),
            consecutivo_final: $request->filled('consecutivo_final') ? (int) $request->input('consecutivo_final') : null,
            fecha_resolucion: $request->filled('fecha_resolucion') ? $request->string('fecha_resolucion')->trim()->toString() : null,
            numero_resolucion: $request->filled('numero_resolucion') ? $request->string('numero_resolucion')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'sucursal_id' => $this->sucursal_id,
            'tipo_comprobante' => $this->tipo_comprobante,
            'prefijo' => $this->prefijo,
            'consecutivo_actual' => $this->consecutivo_actual,
            'consecutivo_inicial' => $this->consecutivo_inicial,
            'consecutivo_final' => $this->consecutivo_final,
            'fecha_resolucion' => $this->fecha_resolucion,
            'numero_resolucion' => $this->numero_resolucion,
            'activo' => $this->activo,
        ];
    }
}
