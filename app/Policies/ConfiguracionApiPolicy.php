<?php

namespace App\Policies;

use App\Models\Usuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConfiguracionApiPolicy extends BasePolicy
{
    use HandlesAuthorization;

    protected string $module = 'configuraciones';

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Usuario $user): bool
    {
        return $user->hasPermissionTo('ver-configuraciones');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('ver-configuraciones');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Usuario $user): bool
    {
        return $user->hasPermissionTo('crear-configuraciones') && $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('editar-configuraciones') && $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('eliminar-configuraciones') && $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('editar-configuraciones') && $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('eliminar-configuraciones') && $user->hasRole('Administrador');
    }
}
