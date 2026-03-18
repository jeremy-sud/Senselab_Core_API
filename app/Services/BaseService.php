<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * Clase base abstracta para servicios con operaciones CRUD.
 *
 * FASE 16: Elimina duplicación de listar/crear/actualizar/eliminar
 * en los 22+ servicios del proyecto.
 *
 * Cada servicio concreto:
 *  - Define $modelClass (FQCN del modelo Eloquent)
 *  - Define $searchFields (campos para búsqueda textual)
 *  - Define $defaultRelations (eager loading por defecto)
 *  - Sobreescribe hooks cuando necesita lógica adicional
 *
 * @template TModel of Model
 */
abstract class BaseService
{
    /** @var class-string<TModel> */
    protected string $modelClass;

    /** @var string[] Campos para búsqueda textual (LIKE %term%) */
    protected array $searchFields = [];

    /** @var array<int, string|array<string, \Closure>> Relaciones eager load por defecto */
    protected array $defaultRelations = [];

    /** @var string Campo de ordenamiento por defecto */
    protected string $defaultOrderBy = 'id';

    /** @var string Dirección de ordenamiento por defecto */
    protected string $defaultOrderDirection = 'asc';

    // ─── CRUD ────────────────────────────────────────────────────

    /**
     * @param array<string, mixed> $filtros
     * @return LengthAwarePaginator<int, TModel>
     */
    public function listar(array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->newQuery();
        $this->applyEagerLoad($query);
        $this->applyFilters($query, $filtros);
        $this->applySearch($query, $filtros);
        $this->applyOrdering($query, $filtros);

        return $query->paginate($perPage);
    }

    /**
     * Listado completo sin paginación (para catálogos pequeños).
     *
     * @param array<string, mixed> $filtros
     * @return \Illuminate\Database\Eloquent\Collection<int, TModel>
     */
    public function listarTodos(array $filtros = []): \Illuminate\Database\Eloquent\Collection
    {
        $query = $this->newQuery();
        $this->applyEagerLoad($query);
        $this->applyFilters($query, $filtros);
        $this->applySearch($query, $filtros);
        $this->applyOrdering($query, $filtros);

        /** @var \Illuminate\Database\Eloquent\Collection<int, TModel> */
        return $query->get();
    }

    /**
     * @param array<string, mixed> $data
     * @return TModel
     */
    public function crear(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $this->beforeCreate($data);
            /** @var TModel $model */
            $model = ($this->modelClass)::create($data);
            $this->afterCreate($model, $data);

            return $model->load($this->getRelationsForDetail());
        });
    }

    /**
     * @return TModel
     */
    public function obtener(int $id): Model
    {
        /** @var TModel */
        return ($this->modelClass)::with($this->getRelationsForDetail())
            ->findOrFail($id);
    }

    /**
     * @param TModel $model
     * @param array<string, mixed> $data
     * @return TModel
     */
    public function actualizar(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            $this->beforeUpdate($model, $data);
            $model->update($data);
            $this->afterUpdate($model, $data);

            return $model->load($this->getRelationsForDetail());
        });
    }

    public function eliminar(Model $model): bool
    {
        return DB::transaction(function () use ($model): bool {
            $this->beforeDelete($model);
            $model->update(['activo' => false, 'eliminado' => true]);

            return true;
        });
    }

    // ─── Hooks (sobreescribir en servicios concretos) ────────────

    /**
     * Se ejecuta antes de crear el modelo.
     * Útil para asignar campos calculados o validar reglas de negocio.
     *
     * @param array<string, mixed> $data
     */
    protected function beforeCreate(array &$data): void {}

    /**
     * Se ejecuta después de crear el modelo.
     * Útil para relaciones, side-effects, etc.
     *
     * @param TModel $model
     * @param array<string, mixed> $data
     */
    protected function afterCreate(Model $model, array $data): void {}

    /**
     * Se ejecuta antes de actualizar el modelo.
     *
     * @param TModel $model
     * @param array<string, mixed> $data
     */
    protected function beforeUpdate(Model $model, array &$data): void {}

    /**
     * Se ejecuta después de actualizar el modelo.
     *
     * @param TModel $model
     * @param array<string, mixed> $data
     */
    protected function afterUpdate(Model $model, array $data): void {}

    /**
     * Se ejecuta antes de eliminar (soft delete).
     * Lanzar excepción para abortar.
     *
     * @param TModel $model
     */
    protected function beforeDelete(Model $model): void {}

    // ─── Query helpers ──────────────────────────────────────────

    /**
     * @return Builder<TModel>
     */
    protected function newQuery(): Builder
    {
        /** @var Builder<TModel> */
        return ($this->modelClass)::query();
    }

    /**
     * @param Builder<TModel> $query
     */
    protected function applyEagerLoad(Builder $query): void
    {
        if (!empty($this->defaultRelations)) {
            $query->with($this->defaultRelations);
        }
    }

    /**
     * Aplica filtros WHERE exactos. Sobreescribible para filtros especiales.
     *
     * @param Builder<TModel> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyFilters(Builder $query, array $filtros): void
    {
        if (!empty($filtros['empresa_id'])) {
            $query->where('empresa_id', $filtros['empresa_id']);
        }

        if (isset($filtros['activo'])) {
            $query->where('activo', (bool) $filtros['activo']);
        }
    }

    /**
     * Aplica búsqueda textual multi-campo.
     *
     * @param Builder<TModel> $query
     * @param array<string, mixed> $filtros
     */
    protected function applySearch(Builder $query, array $filtros): void
    {
        $term = $filtros['search'] ?? '';
        if ($term === '' || empty($this->searchFields)) {
            return;
        }

        $query->where(function (Builder $q) use ($term): void {
            foreach ($this->searchFields as $i => $field) {
                $method = $i === 0 ? 'where' : 'orWhere';
                $q->{$method}($field, 'like', "%{$term}%");
            }
        });
    }

    /**
     * @param Builder<TModel> $query
     * @param array<string, mixed> $filtros
     */
    protected function applyOrdering(Builder $query, array $filtros): void
    {
        $sortBy = $filtros['sort_by'] ?? $this->defaultOrderBy;
        $sortDir = $filtros['sort_order'] ?? $this->defaultOrderDirection;

        $query->orderBy($sortBy, $sortDir);
    }

    /**
     * Relaciones a cargar para vistas de detalle (obtener/crear/actualizar).
     * Por defecto usa $defaultRelations, pero puede sobreescribirse
     * para cargar relaciones más profundas en detalle.
     *
     * @return array<int, string|array<string, \Closure>>
     */
    protected function getRelationsForDetail(): array
    {
        return $this->defaultRelations;
    }
}
