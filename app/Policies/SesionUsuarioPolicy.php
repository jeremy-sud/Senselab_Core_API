<?php

namespace App\Policies;

use App\Models\User;
use App\Models\SesionUsuario;
use Illuminate\Auth\Access\HandlesAuthorization;

class SesionUsuarioPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Usuarios pueden ver sus propias sesiones
        // Admins pueden ver todas las sesiones
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SesionUsuario $sesion): bool
    {
        // Usuario puede ver sus propias sesiones
        if ($sesion->usuario_id === $user->id) {
            return true;
        }

        // Admin puede ver sesiones de su empresa
        return $user->hasPermissionTo('sesiones_usuarios.show') &&
               $user->hasRole('admin') &&
               $user->empresa_id === $sesion->usuario->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Las sesiones se crean automáticamente al hacer login
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SesionUsuario $sesion): bool
    {
        // Las sesiones no se editan manualmente
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SesionUsuario $sesion): bool
    {
        // Usuario puede cerrar sus propias sesiones (logout)
        if ($sesion->usuario_id === $user->id) {
            return true;
        }

        // Admin puede cerrar sesiones de usuarios de su empresa (seguridad)
        return $user->hasPermissionTo('sesiones_usuarios.destroy') &&
               $user->hasRole('admin') &&
               $user->empresa_id === $sesion->usuario->empresa_id;
    }
}
