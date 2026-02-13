<?php

namespace App\Services;

use App\DTOs\API\VentaCreateDTO;
use App\Models\Venta;
use Illuminate\Pagination\Paginator;

/**
 * Servicio para gestionar Ventas
 * 
 * Encapsula la lógica de negocio para ventas
 * Fecha de creación: 12 de febrero de 2026
 */
class VentaService
{
    /**
     * Crear una nueva venta
     */
    public function crear(VentaCreateDTO $dto): Venta
    {
        $venta = Venta::create($dto->toArray());
        
        // Crear detalles de la venta
        foreach ($dto->getDetalles() as $detalle) {
            $venta->detalles()->create($detalle);
        }
        
        return $venta->fresh('detalles');
    }

    /**
     * Obtener venta por ID con sus detalles
     */
    public function obtener(int $ventaId): ?Venta
    {
        return Venta::with('detalles', 'cliente')->find($ventaId);
    }

    /**
     * Listar ventas con paginación
     */
    public function listar(int $perPage = 15): Paginator
    {
        return Venta::with('cliente')->paginate($perPage);
    }

    /**
     * Ventas por cliente
     */
    public function porCliente(int $clienteId, int $perPage = 15): Paginator
    {
        return Venta::where('cliente_id', $clienteId)
            ->with('detalles')
            ->paginate($perPage);
    }

    /**
     * Ventas entre fechas
     */
    public function entreF echas(\DateTime $inicio, \DateTime $fin, int $perPage = 15): Paginator
    {
        return Venta::whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->with('cliente')
            ->orderByDesc('fecha')
            ->paginate($perPage);
    }

    /**
     * Calcular total de ventas en período
     */
    public function totalEnPeriodo(\DateTime $inicio, \DateTime $fin): float
    {
        return (float) Venta::whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->sum('total');
    }

    /**
     * Cambiar estado de venta
     */
    public function cambiarEstado(Venta $venta, string $nuevoEstado): Venta
    {
        $venta->estado = $nuevoEstado;
        $venta->save();
        return $venta->fresh();
    }
}
