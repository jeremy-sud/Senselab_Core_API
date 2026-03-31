<?php

namespace App\Policies;

use App\Models\Usuario;
use App\Models\Webhook;

class WebhookPolicy extends BasePolicy
{
    protected string $permission = 'webhooks';

    /**
     * Verificar que el recurso pertenece a la misma empresa.
     */
    public function view(Usuario $user, Webhook $webhook): bool
    {
        return $this->hasPermission($user, 'ver')
            && $this->ownsResource($user, $webhook);
    }

    public function create(Usuario $user): bool
    {
        return $this->hasPermission($user, 'crear');
    }

    public function update(Usuario $user, Webhook $webhook): bool
    {
        return $this->hasPermission($user, 'editar')
            && $this->ownsResource($user, $webhook);
    }

    public function delete(Usuario $user, Webhook $webhook): bool
    {
        return $this->hasPermission($user, 'eliminar')
            && $this->ownsResource($user, $webhook);
    }
}
