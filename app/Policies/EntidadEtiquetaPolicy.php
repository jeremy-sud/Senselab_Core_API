<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\EntidadEtiqueta;
use Illuminate\Auth\Access\HandlesAuthorization;

class EntidadEtiquetaPolicy extends BasePolicy
{
    use HandlesAuthorization;

    protected string $module = 'etiquetas';

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Usuario $user): bool
    {
        return $user->hasPermissionTo('ver-etiquetas');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('ver-etiquetas');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Usuario $user): bool
    {
        return $user->hasPermissionTo('crear-etiquetas');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('editar-etiquetas');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('eliminar-etiquetas');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('editar-etiquetas');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        return $user->hasPermissionTo('eliminar-etiquetas') && $user->hasRole('Administrador');
    }
}
