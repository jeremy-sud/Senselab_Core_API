<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Caja;
use Illuminate\Auth\Access\HandlesAuthorization;

class CajaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('cajas.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Caja $caja): bool
    {
        return $user->hasPermissionTo('cajas.show') &&
               $user->empresa_id === $caja->sucursal->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo admin/gerentes pueden crear cajas
        return $user->hasPermissionTo('cajas.store') &&
               $user->hasRole('admin|gerente');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Caja $caja): bool
    {
        return $user->hasPermissionTo('cajas.update') &&
               $user->empresa_id === $caja->sucursal->empresa_id &&
               $user->hasRole('admin|gerente');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Caja $caja): bool
    {
        return $user->hasPermissionTo('cajas.destroy') &&
               $user->empresa_id === $caja->sucursal->empresa_id &&
               $user->hasRole('admin');
    }
}
