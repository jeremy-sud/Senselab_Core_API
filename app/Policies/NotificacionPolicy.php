<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Notificacion;
use Illuminate\Auth\Access\HandlesAuthorization;

class NotificacionPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Los usuarios solo ven sus propias notificaciones
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Notificacion $notificacion): bool
    {
        // Solo el destinatario puede ver la notificación
        return $notificacion->usuario_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Sistema y admins pueden crear notificaciones
        return $user->hasPermissionTo('notificaciones.store') &&
               $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Notificacion $notificacion): bool
    {
        // Las notificaciones no se editan, solo se marcan como leídas
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Notificacion $notificacion): bool
    {
        // Solo el destinatario puede eliminar sus notificaciones
        return $notificacion->usuario_id === $user->id;
    }

    /**
     * Determinar si el usuario puede marcar como leída
     */
    public function marcarLeida(User $user, Notificacion $notificacion): bool
    {
        return $notificacion->usuario_id === $user->id;
    }
}
