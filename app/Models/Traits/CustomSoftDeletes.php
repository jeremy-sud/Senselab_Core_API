<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait CustomSoftDeletes
{
    /**
     * Boot del trait de soft deletes personalizados.
     */
    public static function bootCustomSoftDeletes()
    {
        static::addGlobalScope('notDeleted', function (Builder $builder) {
            $builder->where('eliminado', false);
        });
    }

    /**
     * Marcar el modelo como eliminado.
     */
    public function delete()
    {
        $this->eliminado = true;
        $this->activo = false;
        return $this->save();
    }

    /**
     * Restaurar el modelo eliminado.
     */
    public function restore()
    {
        $this->eliminado = false;
        $this->activo = true;
        return $this->save();
    }

    /**
     * Eliminar permanentemente el modelo.
     */
    public function forceDelete()
    {
        return parent::delete();
    }

    /**
     * Scope para incluir registros eliminados.
     */
    public function scopeWithDeleted(Builder $query)
    {
        return $query->withoutGlobalScope('notDeleted');
    }

    /**
     * Scope para solo registros eliminados.
     */
    public function scopeOnlyDeleted(Builder $query)
    {
        return $query->withoutGlobalScope('notDeleted')->where('eliminado', true);
    }

    /**
     * Verificar si el modelo está eliminado.
     */
    public function isDeleted(): bool
    {
        return $this->eliminado === true;
    }

    /**
     * Scope para registros activos.
     */
    public function scopeActivos(Builder $query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para registros inactivos.
     */
    public function scopeInactivos(Builder $query)
    {
        return $query->where('activo', false);
    }
}
