<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\RolPermiso;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolPermisoPolicy extends BasePolicy
{
    use HandlesAuthorization;

    protected string $module = 'roles';

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Usuario $user): bool
    {
        return $user->hasPermissionTo('ver-roles');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('ver-roles');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Usuario $user): bool
    {
        return $user->hasPermissionTo('editar-roles');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('editar-roles');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $rolPermiso = $model;
        if (!$user->hasPermissionTo('editar-roles')) {
            return false;
        }

        // No se pueden eliminar permisos del rol Administrador
        if ($rolPermiso->rol->nombre === 'Administrador') {
            return $user->hasRole('Administrador');
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('editar-roles');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('editar-roles') && $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can assign permissions to a role.
     */
    public function assignPermissions(Usuario $user): bool
    {
        return $user->hasPermissionTo('editar-roles');
    }

    /**
     * Determine whether the user can sync permissions for a role.
     */
    public function syncPermissions(Usuario $user): bool
    {
        return $user->hasPermissionTo('editar-roles');
    }
}
