<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\Etiqueta;
use Illuminate\Auth\Access\HandlesAuthorization;

class EtiquetaPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('etiquetas.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Etiqueta $etiqueta): bool
    {
        return $user->hasPermissionTo('etiquetas.show') &&
               $user->empresa_id === $etiqueta->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('etiquetas.store');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Etiqueta $etiqueta): bool
    {
        return $user->hasPermissionTo('etiquetas.update') &&
               $user->empresa_id === $etiqueta->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Etiqueta $etiqueta): bool
    {
        return $user->hasPermissionTo('etiquetas.destroy') &&
               $user->empresa_id === $etiqueta->empresa_id;
    }
}
