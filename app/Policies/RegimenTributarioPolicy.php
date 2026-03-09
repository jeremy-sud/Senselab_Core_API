<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\RegimenTributario;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegimenTributarioPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('regimenes_tributarios.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, RegimenTributario $regimen): bool
    {
        return $user->hasPermission('regimenes_tributarios.show');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo admin puede crear regímenes tributarios (catálogo maestro)
        return $user->hasPermission('regimenes_tributarios.store') &&
               $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RegimenTributario $regimen): bool
    {
        // Solo admin puede modificar catálogo maestro
        return $user->hasPermission('regimenes_tributarios.update') &&
               $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RegimenTributario $regimen): bool
    {
        // Solo admin puede eliminar regímenes tributarios
        return $user->hasPermission('regimenes_tributarios.destroy') &&
               $user->hasRole('Administrador');
    }
}
