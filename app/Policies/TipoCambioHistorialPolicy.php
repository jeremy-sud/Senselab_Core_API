<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\TipoCambioHistorial;
use Illuminate\Auth\Access\HandlesAuthorization;

class TipoCambioHistorialPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('tipos_cambio_historial.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, TipoCambioHistorial $tipoCambio): bool
    {
        return $user->hasPermission('tipos_cambio_historial.show');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo admin/tesorería pueden registrar tipos de cambio
        return $user->hasPermission('tipos_cambio_historial.store') &&
               $user->hasAnyRole(['Administrador', 'Tesorero']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, TipoCambioHistorial $tipoCambio): bool
    {
        // Solo se permite actualizar el mismo día de registro
        if (!$tipoCambio->created_at->isToday()) {
            return false;
        }

        return $user->hasPermission('tipos_cambio_historial.update') &&
               $user->hasAnyRole(['Administrador', 'Tesorero']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TipoCambioHistorial $tipoCambio): bool
    {
        // Solo se puede eliminar el mismo día de registro
        if (!$tipoCambio->created_at->isToday()) {
            return false;
        }

        return $user->hasPermission('tipos_cambio_historial.destroy') &&
               $user->hasRole('Administrador');
    }
}
