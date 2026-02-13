<?php

namespace App\Services;

use App\DTOs\API\EntradaInventarioCreateDTO;
use App\Models\EntradaInventario;
use Illuminate\Pagination\Paginator;

/**
 * Servicio para gestionar Entradas de Inventario
 *
 * Encapsula la lógica de negocio para entradas de inventario
 * Fecha de creación: 12 de febrero de 2026
 */
class EntradaInventarioService
{
    /**
     * Crear una nueva entrada de inventario
     */
    public function crear(EntradaInventarioCreateDTO $dto): EntradaInventario
    {
        $entrada = EntradaInventario::create($dto->toArray());
        
        // Crear detalles de la entrada
        foreach ($dto->getDetalles() as $detalle) {
            $entrada->detalles()->create($detalle);
        }
        
        return $entrada->fresh('detalles');
    }

    /**
     * Obtener entrada por ID
     */
    public function obtener(int $entradaId): ?EntradaInventario
    {
        return EntradaInventario::with('detalles')->find($entradaId);
    }

    /**
     * Listar entradas con paginación
     */
    public function listar(int $perPage = 15): Paginator
    {
        return EntradaInventario::with('detalles')->paginate($perPage);
    }

    /**
     * Entradas por almacén
     */
    public function porAlmacen(int $almacenId, int $perPage = 15): Paginator
    {
        return EntradaInventario::where('almacen_id', $almacenId)
            ->with('detalles')
            ->paginate($perPage);
    }

    /**
     * Entradas entre fechas
     */
    public function entreFechas(\DateTime $inicio, \DateTime $fin, int $perPage = 15): Paginator
    {
        return EntradaInventario::whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->with('detalles')
            ->orderByDesc('fecha')
            ->paginate($perPage);
    }
}
