<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Builder;

class ReservaService extends BaseService
{
    protected string $modelClass = Reserva::class;
    
    protected array $searchFields = ['servicio', 'notas'];
    
    protected array $defaultRelations = ['cliente', 'usuario'];

    /**
     * @param array<string, mixed> $data
     */
    protected function beforeCreate(array &$data): void
    {
        // El usuario logueado lo podemos pasar desde el request si es necesario
        // pero usualmente se hace en Controller o con auth()->id()
        if (empty($data['estado'])) {
            $data['estado'] = 'pendiente';
        }
    }

    /**
     * @param Builder<Reserva> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        parent::applyFilters($query, $filtros);

        if (!empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        
        if (!empty($filtros['cliente_id'])) {
            $query->where('cliente_id', $filtros['cliente_id']);
        }

        if (!empty($filtros['fecha_desde'])) {
            $query->where('fecha_inicio', '>=', $filtros['fecha_desde']);
        }

        if (!empty($filtros['fecha_hasta'])) {
            $query->where('fecha_inicio', '<=', $filtros['fecha_hasta']);
        }
    }
}
