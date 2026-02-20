<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;
use App\Models\Permiso;

/**
 * Trait para agregar cache de permisos a modelos Usuario y Rol.
 * Mejora significativamente el performance al verificar permisos.
 */
trait HasPermissionCache
{
    /**
     * Duración del cache de permisos en segundos.
     * Default: 1 hora (3600 segundos)
     *
     * @var int
     */
    protected int $permissionCacheDuration = 3600;

    /**
     * Obtener la clave de cache para permisos del usuario/rol.
     *
     * @return string
     */
    protected function getPermissionCacheKey(): string
    {
        $modelType = class_basename(static::class);
        return "permissions.{$modelType}.{$this->id}";
    }

    /**
     * Obtener todos los permisos con cache.
     *
     * @return array Array de slugs de permisos
     */
    public function getCachedPermissions(): array
    {
        return Cache::remember(
            $this->getPermissionCacheKey(),
            $this->permissionCacheDuration,
            function () {
                return $this->loadPermissionsFromDatabase();
            }
        );
    }

    /**
     * Cargar permisos desde la base de datos.
     * Implementación por defecto usando getAllPermissions().
     *
     * @return array
     */
    protected function loadPermissionsFromDatabase(): array
    {
        if (method_exists($this, 'getAllPermissions')) {
            return $this->getAllPermissions();
        }
        
        return [];
    }

    /**
     * Verificar si tiene un permiso usando cache.
     *
     * @param string $permissionSlug Slug del permiso (formato: 'modulo.accion' o 'modulo-accion')
     * @return bool
     */
    public function hasCachedPermission(string $permissionSlug): bool
    {
        $permissions = $this->getCachedPermissions();
        
        // Normalizar el slug para soportar ambos formatos (puntos y guiones)
        $normalizedSlug = str_replace('.', '-', $permissionSlug);
        $alternativeSlug = str_replace('-', '.', $permissionSlug);
        
        return in_array($normalizedSlug, $permissions) ||
               in_array($alternativeSlug, $permissions);
    }

    /**
     * Verificar si tiene alguno de los permisos especificados usando cache.
     *
     * @param array $permissionSlugs Array de slugs de permisos
     * @return bool
     */
    public function hasAnyCachedPermission(array $permissionSlugs): bool
    {
        $permissions = $this->getCachedPermissions();
        
        foreach ($permissionSlugs as $slug) {
            $normalizedSlug = str_replace('.', '-', $slug);
            $alternativeSlug = str_replace('-', '.', $slug);
            
            if (in_array($normalizedSlug, $permissions) ||
                in_array($alternativeSlug, $permissions)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verificar si tiene todos los permisos especificados usando cache.
     *
     * @param array $permissionSlugs Array de slugs de permisos
     * @return bool
     */
    public function hasAllCachedPermissions(array $permissionSlugs): bool
    {
        $permissions = $this->getCachedPermissions();
        
        foreach ($permissionSlugs as $slug) {
            $normalizedSlug = str_replace('.', '-', $slug);
            $alternativeSlug = str_replace('-', '.', $slug);
            
            if (!in_array($normalizedSlug, $permissions) &&
                !in_array($alternativeSlug, $permissions)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Limpiar cache de permisos para este modelo.
     *
     * @return void
     */
    public function clearPermissionCache(): void
    {
        Cache::forget($this->getPermissionCacheKey());
    }

    /**
     * Refrescar cache de permisos.
     * Limpia el cache y lo recarga inmediatamente.
     *
     * @return array
     */
    public function refreshPermissionCache(): array
    {
        $this->clearPermissionCache();
        return $this->getCachedPermissions();
    }

    /**
     * Boot del trait.
     * Limpia cache automáticamente cuando se actualizan relaciones de permisos.
     */
    public static function bootHasPermissionCache(): void
    {
        // Limpiar cache cuando se guarda el modelo
        static::saved(function ($model) {
            if (method_exists($model, 'clearPermissionCache')) {
                $model->clearPermissionCache();
            }
        });

        // Limpiar cache cuando se elimina el modelo
        static::deleted(function ($model) {
            if (method_exists($model, 'clearPermissionCache')) {
                $model->clearPermissionCache();
            }
        });
    }
}
