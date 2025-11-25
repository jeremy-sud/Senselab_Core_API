<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Trait HasEmpresaContext
 *
 * Centraliza el acceso al contexto multi-tenant (empresa) y provee
 * helpers para scoping y verificación de pertenencia.
 *
 * Métodos principales:
 * - getEmpresaId(): int|null Obtiene el ID de la empresa del usuario autenticado.
 * - scopeEmpresa(Builder $query, ?int $empresaId = null): Builder Aplica where empresa_id.
 * - assertEmpresa(Model $model, ?int $empresaId = null): void Lanza excepción si el modelo no pertenece.
 */
trait HasEmpresaContext
{
    /**
     * Obtiene el ID de la empresa del usuario autenticado (sanctum).
     */
    protected function getEmpresaId(): ?int
    {
        $user = Auth::guard('sanctum')->user();
        return $user?->empresa_id;
    }

    /**
     * Aplica scope por empresa_id al query builder.
     */
    protected function scopeEmpresa(Builder $query, ?int $empresaId = null): Builder
    {
        $id = $empresaId ?? $this->getEmpresaId();
        if ($id !== null) {
            $query->where('empresa_id', $id);
        }
        return $query;
    }

    /**
     * Verifica que el modelo pertenece a la empresa actual y lanza excepción si no.
     */
    protected function assertEmpresa(Model $model, ?int $empresaId = null): void
    {
        $id = $empresaId ?? $this->getEmpresaId();
        if ($id === null) {
            throw new AccessDeniedHttpException('Usuario sin empresa asociada.');
        }
        if ((int) $model->getAttribute('empresa_id') !== (int) $id) {
            throw new AccessDeniedHttpException('Acceso denegado: modelo no pertenece a la empresa.');
        }
    }
}
