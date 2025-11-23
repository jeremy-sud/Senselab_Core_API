<?php

namespace App\Policies;

use App\Models\Usuario;
use Illuminate\Database\Eloquent\Model;

/**
 * BasePolicy - Clase base para todas las policies del sistema
 * 
 * Proporciona lógica común de autorización multi-tenant y verificación de permisos.
 * Todas las policies deben extender esta clase.
 * 
 * @package App\Policies
 * @author Jeremy Arias Solano <deadmooncr@gmail.com>
 * @copyright 2025 Sistemas Ursol S.A.
 */
abstract class BasePolicy
{
    /**
     * Prefijo del permiso (ej: 'empresas', 'productos', 'ventas')
     * Debe ser definido en cada policy hija
     * 
     * @var string
     */
    protected string $permission;

    /**
     * Verificar si el usuario pertenece a la misma empresa que el recurso
     * 
     * @param Usuario $user
     * @param Model $model
     * @return bool
     */
    protected function ownsResource(Usuario $user, Model $model): bool
    {
        // Si el modelo tiene empresa_id, verificar que coincida
        if (isset($model->empresa_id)) {
            return $user->empresa_id === $model->empresa_id;
        }
        
        // Si el modelo ES una empresa, verificar que sea la suya
        if ($model instanceof \App\Models\Empresa) {
            return $user->empresa_id === $model->id;
        }
        
        // Por defecto, permitir (para modelos sin multi-tenancy)
        return true;
    }

    /**
     * Verificar si el usuario tiene el permiso requerido
     * 
     * @param Usuario $user
     * @param string $action
     * @return bool
     */
    protected function hasPermission(Usuario $user, string $action): bool
    {
        $permissionName = "{$this->permission}.{$action}";
        return $user->hasPermission($permissionName);
    }

    /**
     * Determinar si el usuario puede ver cualquier modelo
     * 
     * @param Usuario $user
     * @return bool
     */
    public function viewAny(Usuario $user): bool
    {
        return $this->hasPermission($user, 'leer');
    }

    /**
     * Determinar si el usuario puede ver el modelo
     * 
     * @param Usuario $user
     * @param Model $model
     * @return bool
     */
    public function view(Usuario $user, Model $model): bool
    {
        return $this->ownsResource($user, $model) && 
               $this->hasPermission($user, 'leer');
    }

    /**
     * Determinar si el usuario puede crear modelos
     * 
     * @param Usuario $user
     * @return bool
     */
    public function create(Usuario $user): bool
    {
        return $this->hasPermission($user, 'crear');
    }

    /**
     * Determinar si el usuario puede actualizar el modelo
     * 
     * @param Usuario $user
     * @param Model $model
     * @return bool
     */
    public function update(Usuario $user, Model $model): bool
    {
        return $this->ownsResource($user, $model) && 
               $this->hasPermission($user, 'actualizar');
    }

    /**
     * Determinar si el usuario puede eliminar el modelo
     * 
     * @param Usuario $user
     * @param Model $model
     * @return bool
     */
    public function delete(Usuario $user, Model $model): bool
    {
        return $this->ownsResource($user, $model) && 
               $this->hasPermission($user, 'eliminar');
    }

    /**
     * Determinar si el usuario puede restaurar el modelo
     * 
     * @param Usuario $user
     * @param Model $model
     * @return bool
     */
    public function restore(Usuario $user, Model $model): bool
    {
        return $this->ownsResource($user, $model) && 
               $this->hasPermission($user, 'actualizar');
    }

    /**
     * Determinar si el usuario puede forzar eliminación del modelo
     * 
     * @param Usuario $user
     * @param Model $model
     * @return bool
     */
    public function forceDelete(Usuario $user, Model $model): bool
    {
        // Solo administradores pueden forzar eliminación
        return $user->hasRole('Administrador') && 
               $this->ownsResource($user, $model);
    }
}
