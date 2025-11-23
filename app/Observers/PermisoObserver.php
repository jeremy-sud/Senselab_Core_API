<?php

namespace App\Observers;

use App\Models\Permiso;
use App\Services\PermissionService;

/**
 * Observer para el modelo Permiso.
 * Limpia cache automáticamente cuando se modifican permisos.
 */
class PermisoObserver
{
    /**
     * The permission service instance.
     *
     * @var PermissionService
     */
    protected PermissionService $permissionService;

    /**
     * Create a new observer instance.
     *
     * @param PermissionService $permissionService
     */
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    /**
     * Handle the Permiso "created" event.
     *
     * @param  \App\Models\Permiso  $permiso
     * @return void
     */
    public function created(Permiso $permiso): void
    {
        $this->clearRelatedCache($permiso);
    }

    /**
     * Handle the Permiso "updated" event.
     *
     * @param  \App\Models\Permiso  $permiso
     * @return void
     */
    public function updated(Permiso $permiso): void
    {
        $this->clearRelatedCache($permiso);
    }

    /**
     * Handle the Permiso "deleted" event.
     *
     * @param  \App\Models\Permiso  $permiso
     * @return void
     */
    public function deleted(Permiso $permiso): void
    {
        $this->clearRelatedCache($permiso);
    }

    /**
     * Limpiar cache relacionado al permiso.
     *
     * @param \App\Models\Permiso $permiso
     * @return void
     */
    protected function clearRelatedCache(Permiso $permiso): void
    {
        // Limpiar cache de todos los roles que tienen este permiso
        $roles = $permiso->roles;
        foreach ($roles as $rol) {
            $this->permissionService->clearRolePermissionCache($rol);
        }
    }
}
