<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

final class TasaImpuestoCreateDTO
{
    public function __construct(
        public readonly int $tipo_impuesto_id,
        public readonly float $tasa_porcentaje,
        public readonly string $fecha_inicio_vigencia,
        public readonly ?string $fecha_fin_vigencia = null,
        public readonly ?string $descripcion = null,
        public readonly bool $activo = true,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            tipo_impuesto_id: (int) $request->input('tipo_impuesto_id'),
            tasa_porcentaje: (float) $request->input('tasa_porcentaje'),
            fecha_inicio_vigencia: $request->string('fecha_inicio_vigencia')->trim()->toString(),
            fecha_fin_vigencia: $request->filled('fecha_fin_vigencia') ? $request->string('fecha_fin_vigencia')->trim()->toString() : null,
            descripcion: $request->filled('descripcion') ? $request->string('descripcion')->trim()->toString() : null,
            activo: $request->boolean('activo', true),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'tipo_impuesto_id' => $this->tipo_impuesto_id,
            'tasa_porcentaje' => $this->tasa_porcentaje,
            'fecha_inicio_vigencia' => $this->fecha_inicio_vigencia,
            'fecha_fin_vigencia' => $this->fecha_fin_vigencia,
            'descripcion' => $this->descripcion,
            'activo' => $this->activo,
        ];
    }
}
