<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasActiveScope
 *
 * Agrega scopes para filtrar registros activos/inactivos automáticamente.
 * Útil para consultas que solo necesitan registros activos.
 *
 * @package App\Traits
 */
trait HasActiveScope
{
    /**
     * Scope para obtener solo registros activos.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActivo(Builder $query): Builder
    {
        return $query->where($this->getTable() . '.activo', true);
    }

    /**
     * Scope para obtener solo registros inactivos.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInactivo(Builder $query): Builder
    {
        return $query->where($this->getTable() . '.activo', false);
    }

    /**
     * Scope para incluir registros activos e inactivos.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeConInactivos(Builder $query): Builder
    {
        return $query; // No aplica filtro, devuelve todos
    }

    /**
     * Determina si el registro está activo.
     *
     * @return bool
     */
    public function estaActivo(): bool
    {
        return (bool) $this->activo;
    }

    /**
     * Activa el registro.
     *
     * @return bool
     */
    public function activar(): bool
    {
        $this->activo = true;
        return $this->save();
    }

    /**
     * Desactiva el registro.
     *
     * @return bool
     */
    public function desactivar(): bool
    {
        $this->activo = false;
        return $this->save();
    }

    /**
     * Alterna el estado activo/inactivo.
     *
     * @return bool
     */
    public function toggleActivo(): bool
    {
        $this->activo = !$this->activo;
        return $this->save();
    }
}
