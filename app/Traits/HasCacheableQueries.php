<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

/**
 * Trait HasCacheableQueries
 *
 * Proporciona funcionalidad de cache estandarizada para controladores.
 * Implementa un patrón consistente para cachear consultas de índice
 * con invalidación automática en operaciones de escritura.
 *
 * IMPORTANTE: Define estas propiedades en tu controlador:
 * - protected array $cacheTags = ['tag1', 'tag2'];
 * - protected int $cacheTTL = 3600;
 *
 * @package App\Traits
 * @author Sistemas Ursol S.A.
 */
trait HasCacheableQueries
{
    /**
     * Prefijo para las claves de cache
     * Se genera automáticamente basado en el nombre de la clase
     */
    protected function getCachePrefix(): string
    {
        $className = class_basename($this);
        return strtolower(str_replace('Controller', '', $className));
    }

    /**
     * Obtiene el empresa_id del tenant actual para aislamiento de cache.
     */
    protected function getTenantId(): int|string
    {
        if (auth('sanctum')->check()) {
            /** @var \App\Models\Usuario|null $user */
            $user = auth('sanctum')->user();
            return $user?->empresa_id ?? 0;
        }

        return 0;
    }

    /**
     * Genera una clave de cache única basada en parámetros
     *
     * @param string $method Nombre del método (ej: 'index', 'show')
     * @param array<string, mixed> $params Parámetros para generar la clave
     */
    protected function getCacheKey(string $method, array $params = []): string
    {
        $prefix = $this->getCachePrefix();
        $tenantId = $this->getTenantId();

        if (!isset($params['empresa_id'])) {
            $params['empresa_id'] = $tenantId;
        }

        $hash = md5((string) json_encode($params));

        return "tenant_{$tenantId}:{$prefix}:{$method}:{$hash}";
    }

    /**
     * Obtiene el TTL del cache
     * Por defecto: 1 hora
     * Puede ser sobrescrito definiendo $cacheTTL en el controlador
     */
    protected function getCacheTTL(): int
    {
        /** @var int $ttl */
        $ttl = $this->cacheTTL ?? 3600;
        return $ttl;
    }

    /**
     * Obtiene los tags del cache
     * Puede ser sobrescrito definiendo $cacheTags en el controlador
     *
     * @return array<int, string>
     */
    protected function getCacheTags(): array
    {
        /** @var array<int, string> $tags */
        $baseTags = $this->cacheTags ?? [$this->getCachePrefix(), 'catalogos'];
        $tenantId = $this->getTenantId();

        return array_map(
            fn (string $tag): string => "tenant_{$tenantId}:{$tag}",
            $baseTags
        );
    }

    /**
     * Ejecuta una consulta con cache
     *
     * @template T
     * @param string $cacheKey Clave de cache ya generada
     * @param callable(): T $callback Función que retorna los datos a cachear
     * @return T
     */
    protected function cacheQuery(string $cacheKey, callable $callback): mixed
    {
        $cacheTags = $this->getCacheTags();
        $cacheTTL = $this->getCacheTTL();

        return Cache::tags($cacheTags)->remember($cacheKey, $cacheTTL, $callback);
    }

    /**
     * Invalida todo el cache relacionado a este controlador y sus tags
     *
     * Se debe llamar en operaciones de escritura (create, update, delete)
     */
    protected function flushCache(): void
    {
        Cache::tags($this->getCacheTags())->flush();
    }

    /**
     * Invalida una clave específica del cache
     *
     * @param string $key
     * @return void
     */
    protected function forgetCacheKey(string $key): void
    {
        Cache::tags($this->getCacheTags())->forget($key);
    }

    /**
     * Verifica si el cache está habilitado
     * Útil para desactivar cache en testing o debugging
     *
     * @return bool
     */
    protected function isCacheEnabled(): bool
    {
        return config('cache.enabled', true)
            && config('cache.default') !== 'array'
            && !app()->environment('testing');
    }

    /**
     * Wrapper condicional para cache
     * Solo cachea si está habilitado, sino ejecuta directamente
     *
     * @param string $cacheKey Clave de cache ya generada
     * @param callable $callback
     * @return mixed
     */
    protected function cacheQueryIfEnabled(string $cacheKey, callable $callback)
    {
        if ($this->isCacheEnabled()) {
            return $this->cacheQuery($cacheKey, $callback);
        }
        
        return $callback();
    }
    
    /**
     * Alias de getCacheKey para compatibilidad
     *
     * @param string $method
     * @param array<string, mixed> $params
     * @return string
     */
    protected function generateCacheKey(string $method, array $params = []): string
    {
        return $this->getCacheKey($method, $params);
    }
    
    /**
     * Alias de cacheQuery para compatibilidad
     *
     * @param string $cacheKey
     * @param callable $callback
     * @return mixed
     */
    protected function getCached(string $cacheKey, callable $callback)
    {
        return $this->cacheQuery($cacheKey, $callback);
    }
    
    /**
     * Alias de flushCache para compatibilidad
     *
     * @return void
     */
    protected function clearCache(): void
    {
        $this->flushCache();
    }
}
