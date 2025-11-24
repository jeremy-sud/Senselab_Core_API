<?php

namespace App\Policies;

use App\Models\Usuario as User;
use App\Models\Archivo;
use Illuminate\Auth\Access\HandlesAuthorization;

class ArchivoPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('archivos.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Archivo $archivo): bool
    {
        // Usuarios pueden ver archivos de su empresa
        return $user->hasPermissionTo('archivos.show') &&
               $user->empresa_id === $archivo->empresa_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('archivos.store');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Archivo $archivo): bool
    {
        // Solo se permite actualizar metadatos, no el archivo físico
        return $user->hasPermissionTo('archivos.update') &&
               $user->empresa_id === $archivo->empresa_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Archivo $archivo): bool
    {
        // Solo el propietario (usuario que subió) o admin pueden eliminar
        if ($archivo->usuario_id === $user->id) {
            return true;
        }

        return $user->hasPermissionTo('archivos.destroy') &&
               $user->empresa_id === $archivo->empresa_id &&
               $user->hasRole('admin');
    }

    /**
     * Determinar si el usuario puede descargar el archivo
     */
    public function descargar(User $user, Archivo $archivo): bool
    {
        return $user->hasPermissionTo('archivos.descargar') &&
               $user->empresa_id === $archivo->empresa_id;
    }
}
