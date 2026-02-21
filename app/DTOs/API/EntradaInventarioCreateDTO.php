<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de entrada de inventario
 *
 * Valida y transforma datos de entrada para la creación de entradas de inventario
 * Fecha de creación: 12 de febrero de 2026
 */
final class EntradaInventarioCreateDTO
{
    /**
     * @param array<mixed> $detalles
     */
    /**
     * @param array<mixed> $detalles
     */
    public function __construct(
        public readonly int $empresa_id,
        public readonly int $almacen_id,
        public readonly \DateTime $fecha,
        public readonly string $tipo_entrada,
        public readonly float $cantidad_total,
        public readonly ?string $numero_documento = null,
        public readonly ?int $proveedor_id = null,
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
            tipo_entrada: $request->string('tipo_entrada'),
            cantidad_total: $request->float('cantidad_total'),
            numero_documento: $request->filled('numero_documento') ? $request->string('numero_documento')->trim()->toString() : null,
            proveedor_id: $request->integer('proveedor_id'),
            observaciones: $request->filled('observaciones') ? $request->string('observaciones')->trim()->toString() : null,
            detalles: $request->array('detalles'),
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
            'almacen_id' => $this->almacen_id,
            'fecha' => $this->fecha->format('Y-m-d H:i:s'),
            'tipo_entrada' => $this->tipo_entrada,
            'cantidad_total' => $this->cantidad_total,
            'numero_documento' => $this->numero_documento,
            'proveedor_id' => $this->proveedor_id,
            'observaciones' => $this->observaciones,
        ];
    }

    /**
     * Obtener detalles de la entrada
     */
    /**
     * @return array<mixed>
     */
    /**
     * @return array<mixed>
     */
    public function getDetalles(): array
    {
        return $this->detalles;
    }
}
