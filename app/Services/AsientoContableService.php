<?php

namespace App\Services;

use App\DTOs\API\AsientoContableCreateDTO;
use App\Models\AsientoContable;
use Illuminate\Pagination\Paginator;

/**
 * Servicio para gestionar Asientos Contables
 * 
 * Encapsula la lógica de negocio para asientos contables
 * Fecha de creación: 12 de febrero de 2026
 */
class AsientoContableService
{
    /**
     * Crear un nuevo asiento contable
     */
    public function crear(AsientoContableCreateDTO $dto): AsientoContable
    {
        $asiento = AsientoContable::create($dto->toArray());
        
        // Crear detalles del asiento
        foreach ($dto->getDetalles() as $detalle) {
            $asiento->detalles()->create($detalle);
        }
        
        return $asiento->fresh('detalles');
    }

    /**
     * Obtener asiento por ID
     */
    public function obtener(int $asientoId): ?AsientoContable
    {
        return AsientoContable::with('detalles')->find($asientoId);
    }

    /**
     * Listar asientos con paginación
     */
    public function listar(int $perPage = 15): Paginator
    {
        return AsientoContable::with('detalles')->paginate($perPage);
    }

    /**
     * Asientos entre fechas
     */
    public function entreFechas(\DateTime $inicio, \DateTime $fin, int $perPage = 15): Paginator
    {
        return AsientoContable::whereBetween('fecha', [$inicio->format('Y-m-d'), $fin->format('Y-m-d')])
            ->with('detalles')
            ->orderByDesc('fecha')
            ->paginate($perPage);
    }

    /**
     * Validar que el asiento esté balanceado
     */
    public function validarBalanceo(AsientoContable $asiento): bool
    {
        return abs($asiento->total_debe - $asiento->total_haber) < 0.01;
    }
}
