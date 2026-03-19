<?php

namespace App\Services;

use App\Models\Notificacion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/** @extends BaseService<Notificacion> */
class NotificacionService extends BaseService
{
    protected string $modelClass = Notificacion::class;

    protected string $defaultOrderBy = 'id';

    protected string $defaultOrderDirection = 'desc';

    /**
     * @param Builder<Notificacion> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        if (!empty($filtros['usuario_id'])) {
            $query->where('usuario_id', $filtros['usuario_id']);
        }

        if (!empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (isset($filtros['leida'])) {
            if ($filtros['leida']) {
                $query->where('leida', true);
            } else {
                $query->where('leida', false);
            }
        }

        if (isset($filtros['prioridad'])) {
            $query->where('prioridad', '>=', $filtros['prioridad']);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return Notificacion
     */
    public function crear(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $data['prioridad'] = $data['prioridad'] ?? Notificacion::PRIORIDAD_NORMAL;
            $data['leida'] = false;

            /** @var Notificacion */
            return Notificacion::create($data);
        });
    }

    public function marcarLeida(Notificacion $notificacion): Notificacion
    {
        $notificacion->marcarComoLeida();

        /** @var Notificacion */
        return $notificacion->fresh();
    }

    public function marcarTodasLeidas(int $usuarioId): int
    {
        return Notificacion::where('usuario_id', $usuarioId)
            ->where('leida', false)
            ->update([
                'leida' => true,
                'leida_en' => now(),
            ]);
    }

    public function contarNoLeidas(int $usuarioId): int
    {
        return Notificacion::where('usuario_id', $usuarioId)
            ->where('leida', false)
            ->count();
    }

    public function eliminar(Model $model): bool
    {
        $model->delete();

        return true;
    }
}
