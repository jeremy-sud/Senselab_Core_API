<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\Rol;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** @extends BaseService<Rol> */
class RolService extends BaseService
{
    protected string $modelClass = Rol::class;

    protected array $searchFields = ['nombre', 'descripcion'];

    protected array $defaultRelations = ['permisos'];

    /**
     * @param Builder<Rol> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        if (isset($filtros['activo'])) {
            $query->where('activo', (bool) $filtros['activo']);
        }
    }

    /** @return array<int, string|array<string, \Closure>> */
    protected function getRelationsForDetail(): array
    {
        return ['permisos', 'usuarios'];
    }

    /**
     * @param array<string, mixed> $data
     * @return Rol
     */
    public function crear(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $permisos = $data['permisos'] ?? null;
            unset($data['permisos']);

            /** @var Rol $rol */
            $rol = Rol::create($data);

            if ($permisos) {
                $rol->permisos()->attach($permisos);
            }

            return $rol->load($this->getRelationsForDetail());
        });
    }

    /**
     * @param Rol $model
     * @param array<string, mixed> $data
     * @return Rol
     */
    public function actualizar(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            $permisos = $data['permisos'] ?? null;
            unset($data['permisos']);

            $model->update($data);

            if ($permisos !== null) {
                $model->permisos()->sync($permisos);
            }

            return $model->load($this->getRelationsForDetail());
        });
    }

    public function eliminar(Model $model): bool
    {
        /** @var Rol $model */
        if ($model->usuarios()->count() > 0) {
            throw new BusinessException('No se puede eliminar el rol porque tiene usuarios asignados');
        }

        $model->eliminado = now();
        $model->activo = 0;
        $model->save();

        return true;
    }

    /** @param array<int, int> $permisoIds */
    public function asignarPermisos(Rol $rol, array $permisoIds): Rol
    {
        $rol->permisos()->sync($permisoIds);

        /** @var Rol */
        return $rol->load('permisos');
    }

    public function removerPermiso(Rol $rol, int $permisoId): Rol
    {
        $rol->permisos()->detach($permisoId);

        /** @var Rol */
        return $rol->load('permisos');
    }
}
