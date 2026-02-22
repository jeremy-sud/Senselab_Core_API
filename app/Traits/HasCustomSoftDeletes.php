<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Trait HasCustomSoftDeletes
 *
 * Implementa soft deletes usando el campo 'eliminado' existente en lugar de 'deleted_at'.
 * Proporciona métodos similares a SoftDeletes de Laravel pero adaptado a la BD existente.
 *
 * @package App\Traits
 */
trait HasCustomSoftDeletes
{
    /**
     * Boot the soft deleting trait for a model.
     */
    protected static function bootHasCustomSoftDeletes(): void
    {
        static::addGlobalScope('notDeleted', function (Builder $builder) {
            $builder->where($builder->getModel()->getTable() . '.eliminado', false);
        });
    }

    /**
     * Force a hard delete on a soft deleted model.
     *
     * @return bool|null
     */
    public function forceDelete(): ?bool
    {
        return $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey())->forceDelete();
    }

    /**
     * Perform the actual delete query on this model instance.
     *
     * @return void
     */
    protected function runSoftDelete(): void
    {
        $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());

        $this->{$this->getDeletedAtColumn()} = true;

        $updateData = [
            $this->getDeletedAtColumn() => true,
        ];

        // Si el modelo tiene el campo 'activo', también lo desactivamos
        if ($this->isFillable('activo') || in_array('activo', $this->fillable)) {
            $this->activo = false;
            $updateData['activo'] = false;
        }

        if (method_exists($this, 'getDeletedByColumn') && Auth::check()) {
            $this->{$this->getDeletedByColumn()} = Auth::id();
        }

        $query->update($updateData);

        $this->fireModelEvent('trashed', false);
    }

    /**
     * Restore a soft-deleted model instance.
     *
     * @return bool
     */
    public function restore(): bool
    {
        // Si el modelo no está eliminado, no hacer nada
        if (!$this->{$this->getDeletedAtColumn()}) {
            return true;
        }

        $this->{$this->getDeletedAtColumn()} = false;

        // Disparar evento de restauración
        if ($this->fireModelEvent('restoring') === false) {
            return false;
        }

        // Usar query update en lugar de save() para evitar problemas con exists
        $query = $this->newQueryWithoutScopes()->where($this->getKeyName(), $this->getKey());
        $result = $query->update([
            $this->getDeletedAtColumn() => false,
        ]);

        // Sincronizar el modelo con los cambios
        $this->syncOriginal();

        $this->fireModelEvent('restored', false);

        return $result > 0;
    }

    /**
     * Determine if the model instance has been soft-deleted.
     * 
     * @return bool
     */
    public function trashed(): bool
    {
        return (bool) $this->{$this->getDeletedAtColumn()};
    }

    /**
     * Get the name of the "deleted at" column.
     * 
     * @return string
     */
    public function getDeletedAtColumn(): string
    {
        return 'eliminado';
    }

    /**
     * Get a new query builder that includes soft deleted models.
     *
     * @return Builder
     */
    public static function withTrashed(): Builder
    {
        return static::query()->withoutGlobalScope('notDeleted');
    }

    /**
     * Get a new query builder that only includes soft deleted models.
     *
     * @return Builder
     */
    public static function onlyTrashed(): Builder
    {
        return static::query()->withoutGlobalScope('notDeleted')->where('eliminado', true);
    }

    /**
     * Determine if the model is currently force deleting.
     *
     * @return bool
     */
    public function isForceDeleting(): bool
    {
        return $this->forceDeleting ?? false;
    }

    /**
     * Register a "restoring" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restoring($callback): void
    {
        static::registerModelEvent('restoring', $callback);
    }

    /**
     * Register a "restored" model event callback with the dispatcher.
     *
     * @param  \Closure|string  $callback
     * @return void
     */
    public static function restored($callback): void
    {
        static::registerModelEvent('restored', $callback);
    }

    /**
     * Perform the actual delete query on this model instance.
     * Override the default delete to use soft delete.
     *
     * @return bool|null
     */
    public function delete(): ?bool
    {
        if (is_null($this->getKeyName())) {
            throw new \Exception('No primary key defined on model.');
        }

        if (!$this->exists) {
            return false;
        }

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        $this->runSoftDelete();

        $this->exists = false;

        $this->fireModelEvent('deleted', false);

        return true;
    }
}
