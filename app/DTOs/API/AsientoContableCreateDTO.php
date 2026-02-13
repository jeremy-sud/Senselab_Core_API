<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de asientos contables
 * 
 * Valida y transforma datos de entrada para la creación de asientos contables
 * Fecha de creación: 12 de febrero de 2026
 */
final class AsientoContableCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly \DateTime $fecha,
        public readonly string $descripcion,
        public readonly string $concepto,
        public readonly float $total_debe,
        public readonly float $total_haber,
        public readonly ?string $referencia = null,
        public readonly ?int $proyecto_id = null,
        public readonly array $detalles = [],
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: $request->integer('empresa_id'),
            fecha: new \DateTime($request->string('fecha')),
            descripcion: $request->string('descripcion')->trim(),
            concepto: $request->string('concepto')->trim(),
            total_debe: $request->float('total_debe'),
            total_haber: $request->float('total_haber'),
            referencia: $request->string('referencia')?->trim(),
            proyecto_id: $request->integer('proyecto_id'),
            detalles: $request->array('detalles', []),
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    public function toArray(): array
    {
        return [
            'empresa_id' => $this->empresa_id,
            'fecha' => $this->fecha->format('Y-m-d'),
            'descripcion' => $this->descripcion,
            'concepto' => $this->concepto,
            'total_debe' => $this->total_debe,
            'total_haber' => $this->total_haber,
            'referencia' => $this->referencia,
            'proyecto_id' => $this->proyecto_id,
        ];
    }

    /**
     * Obtener detalles del asiento
     */
    public function getDetalles(): array
    {
        return $this->detalles;
    }
}
