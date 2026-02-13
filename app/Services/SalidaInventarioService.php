<?php

namespace App\Services;

use App\DTOs\API\SalidaInventarioCreateDTO;
use App\Models\SalidaInventario;
use Illuminate\Pagination\Paginator;

/**
 * Servicio para gestionar Salidas de Inventario
 * 
 * Encapsula la lógica de negocio para salidas de inventario
 * Fecha de creación: 12 de febrero de 2026
 */
class SalidaInventarioService
{
    /**
     * Crear una nueva salida de inventario
     */
    public function crear(SalidaInventarioCreateDTO $dto): SalidaInventario
    {
        $salida = SalidaInventario::create($dto->toArray());
        
        // Crear detalles de la salida
        foreach ($dto->getDetalles() as $detalle) {
            $salida->detalles()->create($detalle);
        }
        
        return $salida->fresh('detalles');
    }

    /**
     * Obtener salida por ID
     */
    public function obtener(int $salidaId): ?SalidaInventario
    {
        return SalidaInventario::with('detalles')->find($salidaId);
    }

    /**
     * Listar salidas con paginación
     */
    public function listar(int $perPage = 15): Paginator
    {
        return SalidaInventario::with('detalles')->paginate($perPage);
    }

    /**
     * Salidas por almacén
     */
    public function porAlmacen(int $almacenId, int $perPage = 15): Paginator
    {
        return SalidaInventario::where('almacen_id', $almacenId)
            ->with('detalles')
            ->paginate($perPage);
    }

    /**
     * Salidas entre fechas
     */
    public function entreFechas(\DateTime $inicio, \DateTime $fin, int $perPage = 15): Paginator
    {
        return SalidaInventario::whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->with('detalles')
            ->orderByDesc('fecha')
            ->paginate($perPage);
    }
}
