<?php

namespace App\Policies;

use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeLineaDetallePolicy extends BasePolicy
{
    use HandlesAuthorization;

    protected string $module = 'facturacion_electronica';

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Usuario $user): bool
    {
        return $user->hasPermission('ver-facturacion_electronica');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $lineaDetalle = $model;
        if (!$user->hasPermission('ver-facturacion_electronica')) {
            return false;
        }

        // Verificar a través del comprobante
        return $lineaDetalle->comprobante->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Usuario $user): bool
    {
        return $user->hasPermission('crear-facturacion_electronica');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $lineaDetalle = $model;
        if (!$user->hasPermission('editar-facturacion_electronica')) {
            return false;
        }

        // No se puede editar si el comprobante está aceptado
        if ($lineaDetalle->comprobante->estado === 'aceptado') {
            return false;
        }

        return $lineaDetalle->comprobante->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $lineaDetalle = $model;
        if (!$user->hasPermission('eliminar-facturacion_electronica')) {
            return false;
        }

        // Solo se pueden eliminar líneas de comprobantes en borrador
        if ($lineaDetalle->comprobante->estado !== 'borrador') {
            return false;
        }

        return $lineaDetalle->comprobante->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $lineaDetalle = $model;
        return $user->hasPermission('editar-facturacion_electronica')
            && $lineaDetalle->comprobante->empresa_id === $user->empresa_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $lineaDetalle = $model;
        return $user->hasPermission('eliminar-facturacion_electronica')
            && $user->hasRole('Administrador')
            && $lineaDetalle->comprobante->empresa_id === $user->empresa_id;
    }
}
