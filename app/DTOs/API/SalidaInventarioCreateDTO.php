<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de salida de inventario
 *
 * Valida y transforma datos de entrada para la creación de salidas de inventario
 * Fecha de creación: 12 de febrero de 2026
 */
final class SalidaInventarioCreateDTO
{
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $almacen_id,
        public readonly \DateTime $fecha,
        public readonly string $tipo_salida,
        public readonly float $cantidad_total,
        public readonly ?int $venta_id = null,
        public readonly ?string $observaciones = null,
        public readonly array $detalles = [],
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            empresa_id: $request->integer('empresa_id'),
            almacen_id: $request->integer('almacen_id'),
            fecha: new \DateTime($request->string('fecha')),
            tipo_salida: $request->string('tipo_salida'),
            cantidad_total: $request->float('cantidad_total'),
            venta_id: $request->integer('venta_id'),
            observaciones: $request->string('observaciones')?->trim(),
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
            'almacen_id' => $this->almacen_id,
            'fecha' => $this->fecha->format('Y-m-d H:i:s'),
            'tipo_salida' => $this->tipo_salida,
            'cantidad_total' => $this->cantidad_total,
            'venta_id' => $this->venta_id,
            'observaciones' => $this->observaciones,
        ];
    }

    /**
     * Obtener detalles de la salida
     */
    public function getDetalles(): array
    {
        return $this->detalles;
    }
}
