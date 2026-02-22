<?php

namespace App\Policies;

use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolUsuarioPolicy extends BasePolicy
{
    use HandlesAuthorization;

    protected string $module = 'usuarios';

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Usuario $user): bool
    {
        return $user->hasPermissionTo('ver-usuarios');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('ver-usuarios');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Usuario $user): bool
    {
        return $user->hasPermissionTo('editar-usuarios');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $rolUsuario = $model;
        if (!$user->hasPermissionTo('editar-usuarios')) {
            return false;
        }

        // Un usuario no puede modificar sus propios roles
        if ($rolUsuario->usuario_id === $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        $rolUsuario = $model;
        if (!$user->hasPermissionTo('editar-usuarios')) {
            return false;
        }

        // Un usuario no puede eliminar sus propios roles
        if ($rolUsuario->usuario_id === $user->id) {
            return false;
        }

        // No se puede eliminar el último rol de Administrador de un usuario
        $targetUser = Usuario::find($rolUsuario->usuario_id);
        if ($targetUser && $targetUser->hasRole('Administrador')) {
            $adminRolesCount = $targetUser->roles()->where('nombre', 'Administrador')->count();
            if ($adminRolesCount <= 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('editar-usuarios');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('editar-usuarios') && $user->hasRole('Administrador');
    }
}
