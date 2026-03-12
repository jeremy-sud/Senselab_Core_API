<?php

namespace App\Services;

use App\Models\Usuario;
use App\Models\Rol;
use App\Models\Permiso;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Servicio centralizado para gestión de permisos y autorización.
 * Proporciona métodos optimizados con cache para verificación de permisos.
 */
class PermissionService
{
    /**
     * Duración del cache en segundos (1 hora por defecto).
     *
     * @var int
     */
    protected int $cacheDuration = 3600;

    /**
     * Verificar si un usuario tiene un permiso específico.
     * Usa cache para mejor performance.
     *
     * @param Usuario $usuario
     * @param string $permissionSlug Slug del permiso (formato: 'modulo.accion' o 'modulo-accion')
     * @return bool
     */
    public function userHasPermission(Usuario $usuario, string $permissionSlug): bool
    {
        return $usuario->hasCachedPermission($permissionSlug);
    }

    /**
     * Verificar si un usuario tiene alguno de los permisos especificados.
     *
     * @param Usuario $usuario
     * @param array<int, string> $permissionSlugs
     * @return bool
     */
    public function userHasAnyPermission(Usuario $usuario, array $permissionSlugs): bool
    {
        return $usuario->hasAnyCachedPermission($permissionSlugs);
    }

    /**
     * Verificar si un usuario tiene todos los permisos especificados.
     *
     * @param Usuario $usuario
     * @param array<int, string> $permissionSlugs
     * @return bool
     */
    public function userHasAllPermissions(Usuario $usuario, array $permissionSlugs): bool
    {
        return $usuario->hasAllCachedPermissions($permissionSlugs);
    }

    /**
     * Obtener todos los permisos de un usuario con cache.
     *
     * @param Usuario $usuario
     * @return array<int, string>
     */
    public function getUserPermissions(Usuario $usuario): array
    {
        return $usuario->getCachedPermissions();
    }

    /**
     * Obtener todos los permisos de un rol con cache.
     *
     * @param Rol $rol
     * @return array<int, string>
     */
    public function getRolePermissions(Rol $rol): array
    {
        return $rol->getCachedPermissions();
    }

    /**
     * Limpiar cache de permisos de un usuario específico.
     *
     * @param Usuario $usuario
     * @return void
     */
    public function clearUserPermissionCache(Usuario $usuario): void
    {
        $usuario->clearPermissionCache();
    }

    /**
     * Limpiar cache de permisos de un rol específico.
     *
     * @param Rol $rol
     * @return void
     */
    public function clearRolePermissionCache(Rol $rol): void
    {
        $rol->clearPermissionCache();
    }

    /**
     * Limpiar todo el cache de permisos del sistema.
     * Útil después de actualizaciones masivas de permisos.
     *
     * @return int Número de entradas eliminadas
     */
    public function clearAllPermissionCache(): int
    {
        $pattern = 'permissions.*';
        $keys = [];
        
        // Obtener todas las claves que coincidan con el patrón
        // Nota: Esto funciona con cache de base de datos/file
        // Para Redis, necesitarías usar scan
        
        if (config('cache.default') === 'database') {
            $keys = DB::table(config('cache.stores.database.table'))
                ->where('key', 'like', config('cache.prefix') . 'permissions.%')
                ->pluck('key')
                ->toArray();
            
            foreach ($keys as $key) {
                // Remover el prefijo
                $cleanKey = str_replace(config('cache.prefix'), '', $key);
                Cache::forget($cleanKey);
            }
        } else {
            // Para otros drivers, limpiar todo el cache (menos agresivo)
            Cache::flush();
        }
        
        return count($keys);
    }

    /**
     * Asignar permisos a un rol y actualizar cache.
     *
     * @param Rol $rol
     * @param array<int, int> $permisoIds
     * @return void
     */
    public function assignPermissionsToRole(Rol $rol, array $permisoIds): void
    {
        $rol->syncPermissions($permisoIds);
        $rol->refreshPermissionCache();
        
        // También limpiar cache de todos los usuarios con este rol
        $this->clearCacheForUsersWithRole($rol);
    }

    /**
     * Asignar roles a un usuario y actualizar cache.
     *
     * @param Usuario $usuario
     * @param array<int, int> $roleIds
     * @return void
     */
    public function assignRolesToUser(Usuario $usuario, array $roleIds): void
    {
        $usuario->assignRoles($roleIds);
        $usuario->refreshPermissionCache();
    }

    /**
     * Limpiar cache de todos los usuarios que tienen un rol específico.
     *
     * @param Rol $rol
     * @return int Número de usuarios afectados
     */
    protected function clearCacheForUsersWithRole(Rol $rol): int
    {
        $usuarios = Usuario::select('id', 'empresa_id')
            ->whereHas('roles', function ($query) use ($rol) {
                $query->where('roles.id', $rol->id);
            })->get();
        
        foreach ($usuarios as $usuario) {
            $usuario->clearPermissionCache();
        }
        
        return $usuarios->count();
    }

    /**
     * Obtener estadísticas del cache de permisos.
     *
     * @return array<string, mixed>
     */
    public function getCacheStats(): array
    {
        $stats = [
            'driver' => config('cache.default'),
            'total_permissions' => Permiso::activos()->count(),
            'total_roles' => Rol::activos()->count(),
            'total_users' => Usuario::activos()->count(),
            'cache_entries' => 0,
        ];
        
        if (config('cache.default') === 'database') {
            $stats['cache_entries'] = DB::table(config('cache.stores.database.table'))
                ->where('key', 'like', config('cache.prefix') . 'permissions.%')
                ->count();
        }
        
        return $stats;
    }

    /**
     * Precalentar cache de permisos para usuarios activos.
     * Útil para ejecutar en horarios de bajo tráfico.
     *
     * @param int|null $limit Límite de usuarios a procesar (null = todos)
     * @return int Número de usuarios procesados
     */
    public function warmupPermissionCache(?int $limit = null): int
    {
        $query = Usuario::activos()->with('roles.permisos');
        
        if ($limit) {
            $query->limit($limit);
        }
        
        $usuarios = $query->get();
        
        foreach ($usuarios as $usuario) {
            $usuario->refreshPermissionCache();
        }
        
        return $usuarios->count();
    }
}
