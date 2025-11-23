<?php

namespace App\Observers;

use App\Models\Rol;
use App\Services\PermissionService;
use Illuminate\Support\Facades\DB;

/**
 * Observer para el modelo Rol.
 * Limpia cache automáticamente cuando se modifican roles o sus permisos.
 */
class RolObserver
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
     * Handle the Rol "updated" event.
     *
     * @param  \App\Models\Rol  $rol
     * @return void
     */
    public function updated(Rol $rol): void
    {
        $this->clearRelatedCache($rol);
    }

    /**
     * Handle the Rol "deleted" event.
     *
     * @param  \App\Models\Rol  $rol
     * @return void
     */
    public function deleted(Rol $rol): void
    {
        $this->clearRelatedCache($rol);
    }

    /**
     * Limpiar cache relacionado al rol.
     *
     * @param \App\Models\Rol $rol
     * @return void
     */
    protected function clearRelatedCache(Rol $rol): void
    {
        // Limpiar cache del rol
        $this->permissionService->clearRolePermissionCache($rol);
        
        // Limpiar cache de todos los usuarios que tienen este rol
        $usuarios = $rol->usuarios;
        foreach ($usuarios as $usuario) {
            $this->permissionService->clearUserPermissionCache($usuario);
        }
    }
}
