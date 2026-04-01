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
    public function view(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        /** @var Webhook $model */
        return $this->hasPermission($user, 'ver')
            && $this->ownsResource($user, $model);
    }

    public function create(Usuario $user): bool
    {
        return $this->hasPermission($user, 'crear');
    }

    public function update(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        /** @var Webhook $model */
        return $this->hasPermission($user, 'editar')
            && $this->ownsResource($user, $model);
    }

    public function delete(Usuario $user, \Illuminate\Database\Eloquent\Model $model): bool
    {
        /** @var Webhook $model */
        return $this->hasPermission($user, 'eliminar')
            && $this->ownsResource($user, $model);
    }
}
