<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de período de nómina
 *
 * Valida y transforma datos de entrada para la creación de períodos de nómina
 * Fecha de creación: 12 de febrero de 2026
 */
final class PeriodoNominaCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly \DateTime $fecha_inicio,
        public readonly \DateTime $fecha_fin,
        public readonly string $numero_periodo,
        public readonly string $estado,
        public readonly ?string $observaciones = null,
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: $request->integer('empresa_id'),
            fecha_inicio: new \DateTime($request->string('fecha_inicio')),
            fecha_fin: new \DateTime($request->string('fecha_fin')),
            numero_periodo: $request->string('numero_periodo')->trim(),
            estado: $request->string('estado'),
            observaciones: $request->string('observaciones')?->trim(),
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'fecha_inicio' => $this->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $this->fecha_fin->format('Y-m-d'),
            'numero_periodo' => $this->numero_periodo,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
        ];
    }
}
