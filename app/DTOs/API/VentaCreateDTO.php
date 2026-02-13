<?php

namespace App\DTOs\API;

use Illuminate\Http\Request;

/**
 * DTO para creación de ventas
 * 
 * Valida y transforma datos de entrada para la creación de ventas
 * Fecha de creación: 12 de febrero de 2026
 */
final class VentaCreateDTO
{
    public function __construct(
        public readonly int $cliente_id,
        public readonly int $empresa_id,
        public readonly int $sucursal_id,
        public readonly int $usuario_id,
        public readonly string $fecha_venta,
        public readonly string $tipo_comprobante,
        public readonly ?int $almacen_id = null,
        public readonly ?int $forma_pago_id = null,
        public readonly ?string $tipo_pago = null,
        public readonly ?string $estado = 'Completada',
        public readonly ?string $observaciones = null,
        public readonly array $detalles = [],
    ) {}

    /**
     * Crear DTO desde Request
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            cliente_id: (int) $request->input('cliente_id'),
            empresa_id: (int) $request->input('empresa_id'),
            sucursal_id: (int) $request->input('sucursal_id'),
            usuario_id: (int) ($request->input('usuario_id') ?? auth()->id()),
            fecha_venta: $request->input('fecha_venta') ?? now()->format('Y-m-d'),
            tipo_comprobante: (string) $request->input('tipo_comprobante', 'factura'),
            almacen_id: $request->filled('almacen_id') ? (int) $request->input('almacen_id') : null,
            forma_pago_id: $request->filled('forma_pago_id') ? (int) $request->input('forma_pago_id') : null,
            tipo_pago: $request->input('tipo_pago') ? (string) $request->input('tipo_pago') : null,
            estado: (string) $request->input('estado', 'Completada'),
            observaciones: $request->input('observaciones') ? trim((string) $request->input('observaciones')) : null,
            detalles: $request->input('detalles', []),
        );
    }

    /**
     * Convertir a array para guardar en base de datos
     */
    public function toArray(): array
    {
        return [
            'cliente_id' => $this->cliente_id,
            'empresa_id' => $this->empresa_id,
            'sucursal_id' => $this->sucursal_id,
            'usuario_id' => $this->usuario_id,
            'fecha_venta' => $this->fecha_venta,
            'tipo_comprobante' => $this->tipo_comprobante,
            'forma_pago_id' => $this->forma_pago_id,
            'tipo_pago' => $this->tipo_pago,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
        ];
    }

    /**
     * Getter para sucursal_id 
     */
    public function getSucursalId(): int
    {
        return $this->sucursal_id;
    }

    /**
     * Getter para cliente_id
     */
    public function getClienteId(): int
    {
        return $this->cliente_id;
    }

    /**
     * Getter para empresa_id
     */
    public function getEmpresaId(): int
    {
        return $this->empresa_id;
    }

    /**
     * Getter para usuario_id
     */
    public function getUsuarioId(): int
    {
        return $this->usuario_id;
    }

    /**
     * Getter para almacen_id
     */
    public function getAlmacenId(): ?int
    {
        return $this->almacen_id;
    }

    /**
     * Getter para detalles
     */
    public function getDetalles(): array
    {
        return $this->detalles;
    }

    /**
     * Getter para producto_ids
     */
    public function getProductoIds(): array
    {
        return array_map(fn($detalle) => $detalle['producto_id'], $this->detalles);
    }
}
